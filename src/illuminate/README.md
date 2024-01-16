Ideally we would require illuminate/testing but that's impossible as of 2023-12-22. The reason being,

- Craft requires illuminate/collections ^9.52
- The new version of Pest that we want to use requires phpunit/phpunit ^10.0
- illuminate/testing for phpunit/phpunit ^10.0 requires illiminate/collections ^10.0
- We can't use an older version of illuminate/testing because it would require us to downgrade PHPUnit to 9.x, which we
  don't want to do because PHPUnit 10 is the whole reason we're here.

Once Craft upgrades to illuminate/collections ^10.0, we can remove this entire folder and just use illuminate/testing. 
