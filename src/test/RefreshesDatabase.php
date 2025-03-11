<?php

namespace markhuot\craftpest\test;

use Composer\InstalledVersions;
use Composer\Semver\VersionParser;
use markhuot\craftpest\events\FactoryStoreEvent;
use markhuot\craftpest\events\RollbackTransactionEvent;
use markhuot\craftpest\exceptions\AutoCommittingFieldsException;
use markhuot\craftpest\factories\Factory;
use markhuot\craftpest\factories\Field;
use yii\base\Event;
use yii\db\Transaction;

trait RefreshesDatabase
{
    /**
     * @var bool
     */
    public static $projectConfigCheckedOnce = false;

    /**
     * The config version before the test ran, so we can re-set it back after
     *
     * @var string
     */
    public $oldConfigVersion;

    /**
     * @var Transaction
     */
    protected $transaction;

    /**
     * Whether the current transaction has tried to write entries, elements, etc… to the
     * database already. Because MySQL has an implicit COMMIT on `ALTER TABLE` queries we
     * have to make sure that all `Field::factory()` calls are first so we can manually
     * roll field changes back.
     *
     * @var bool
     */
    protected $hasStoredNonFieldContent = false;

    /**
     * An array of models that were auto committed to the database and must be manually rolled
     * back because they live outside of the transaction lifecycle.
     *
     * @var array
     */
    protected $autoCommittedModels = [];

    public function setUpRefreshesDatabase(): void
    {
        $this->listenForStores();
        $this->beginTransaction();
    }

    protected function tearDownRefreshesDatabase()
    {
        $this->rollBackTransaction();
        $this->rollBackAutoCommittedModels();
        $this->stopListeningForStores();
    }

    protected function listenForStores()
    {
        $this->hasStoredNonFieldContent = false;

        Event::on(Factory::class, Factory::EVENT_BEFORE_STORE, [$this, 'beforeStore']);
        Event::on(Factory::class, Factory::EVENT_AFTER_STORE, [$this, 'afterStore']);
    }

    protected function stopListeningForStores()
    {
        Event::off(Factory::class, Factory::EVENT_BEFORE_STORE, [$this, 'beforeStore']);
        Event::off(Factory::class, Factory::EVENT_AFTER_STORE, [$this, 'afterStore']);
    }

    public function beforeStore(FactoryStoreEvent $event): void
    {
        $isFieldFactory = $event->sender instanceof \markhuot\craftpest\factories\Field || is_subclass_of($event->sender, Field::class);
        $isCraft4 = InstalledVersions::satisfies(new VersionParser, 'craftcms/cms', '~4.0');

        // We don't need to worry about autocommiting fields in Craft 5 because there is no longer
        // a dynamic content table. The field data goes in a JSON field so the DB schema never
        // changes!
        if ($isCraft4 && $isFieldFactory && $this->hasStoredNonFieldContent) {
            throw new AutoCommittingFieldsException('You can not create fields after creating elements while refreshesDatabase is in use.');
        }

        if (! $isFieldFactory) {
            $this->hasStoredNonFieldContent = true;
        }
    }

    public function afterStore(FactoryStoreEvent $event): void
    {
        // If Yii thinks we're in a transaction but the transaction isn't
        // active anymore (probably because it was autocommitted) then we
        // need to do the cleanup ourselves, manually.
        //
        // An example of this is autocommitting DDL transactions like adding
        // a field. When a field is added any in-progress transactions are
        // automatically committed. TO work around that we catch here that
        // we _should_ be in a transaction, but no longer are. If we're in
        // that orphaned state, then store the model that put us in this state
        // (so it can be manually cleaned up later) and re-set our state so
        // subsequent stores can go in to a transaction, as normal.
        $transaction = \Craft::$app->db->getTransaction();
        if ($transaction && ! \Craft::$app->db->pdo->inTransaction()) {
            $this->autoCommittedModels[] = $event->model;

            $transaction->commit();
            $this->beginTransaction();
        }
    }

    public function beginTransaction(): void
    {
        $this->oldConfigVersion = \Craft::$app->info->configVersion;
        $this->transaction = \Craft::$app->db->beginTransaction();
    }

    public function rollBackTransaction(): void
    {
        if (empty($this->transaction)) {
            return;
        }

        $this->transaction->rollBack();

        $event = new RollbackTransactionEvent;
        $event->sender = $this;
        Event::trigger(RefreshesDatabase::class, 'EVENT_ROLLBACK_TRANSACTION', $event);

        \Craft::$app->info->configVersion = $this->oldConfigVersion;
        $this->transaction = null;
    }

    public function rollBackAutoCommittedModels(): void
    {
        foreach ($this->autoCommittedModels as $model) {
            if (is_a($model, \craft\base\Field::class) || is_subclass_of($model, \craft\base\Field::class)) {
                \Craft::$app->fields->deleteField($model);
            } else {
                throw new \Exception('Found orphaned model ['.$model::class.'] that was not cleaned up in a transaction and of an unknown type for craft-pest to clean up. You must remove this model manually.');
            }
        }
    }
}
