# Naming Conventions

In this example, we would like to share some simple rules to apply some naming conventions in your project.

Naming conventions can help the team to create and rename new classes with a common shared standard.   

In the following rules, written into `phparkitect.php` file into the root of a project, we said that:
```
* All classes inside namespace App\Infrastructure\Controller should end with Controller
* All classes inside namespace App\Domain\Auction\Bids should start with Bid
```

The code of our rules:

```
$rule_1 = Rule::allClasses()
        ->that(new ResideInOneOfTheseNamespaces('App\Infrastructure\Controller'))
        ->should(new HaveNameMatching('*Controller'))
        ->because('we want to uniform controller name.');
        
$rule_2 = Rule::allClasses()
        ->that(new ResideInOneOfTheseNamespaces('App\Domain\Auction\Bids'))
        ->should(new HaveNameMatching('Bid*'))
        ->because('we want to uniform bids object inside our domain.');
```
