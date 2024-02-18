<?php

namespace markhuot\craftpest;

use Composer\Semver\Semver;
use Craft;
use craft\base\Field;
use craft\elements\db\ElementQuery;
use craft\elements\Entry;
use craft\events\DefineBehaviorsEvent;
use markhuot\craftpest\behaviors\ExpectableBehavior;
use markhuot\craftpest\behaviors\FieldTypeHintBehavior;
use markhuot\craftpest\behaviors\TestableElementBehavior;
use markhuot\craftpest\behaviors\TestableElementQueryBehavior;
use markhuot\craftpest\console\PestController;
use markhuot\craftpest\interfaces\SectionsServiceInterface;
use yii\base\BootstrapInterface;
use yii\base\Event;

/**
 * @method static self getInstance()
 */
class Pest implements BootstrapInterface
{
    public function bootstrap($app)
    {
        Craft::setAlias('@markhuot/craftpest', __DIR__);

        if (Craft::$app->request->isConsoleRequest) {
            Craft::$app->controllerMap['pest'] = PestController::class;
        }

        Event::on(
            Entry::class,
            Entry::EVENT_DEFINE_BEHAVIORS,
            function (DefineBehaviorsEvent $event) {
                $event->behaviors['expectableBehavior'] = ExpectableBehavior::class;
                $event->behaviors['testableElementBehavior'] = TestableElementBehavior::class;
            }
        );

        Event::on(
            ElementQuery::class,
            ElementQuery::EVENT_DEFINE_BEHAVIORS,
            function (DefineBehaviorsEvent $event) {
                $event->behaviors['testableElementQueryBehavior'] = TestableElementQueryBehavior::class;
            }
        );

        Event::on(
            Field::class,
            Field::EVENT_DEFINE_BEHAVIORS,
            function (DefineBehaviorsEvent $event) {
                $event->behaviors['fieldTypeHintBehavior'] = FieldTypeHintBehavior::class;
            }
        );

        Craft::$container->set(SectionsServiceInterface::class, function () {
            return Semver::satisfies(Craft::$app->version, '~5.0.0') ?
                Craft::$app->getEntries() : // @phpstan-ignore-line
                Craft::$app->getSections(); // @phpstan-ignore-line
        });
    }
}
