<?php

use markhuot\craftpest\Pest;

return [
    'components' => [
        'queue' => [
            'class' => \yii\queue\sync\Queue::class,
            'handle' => true, // if tasks should be executed immediately
        ],
    ],
    'bootstrap' => [
        function ($app) {
            (new Pest)->bootstrap($app);
        },
      ]
];
