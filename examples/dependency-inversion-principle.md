# Dependency Inversion Principle

In this example, we would like to share some simple rules to apply the dependency inversion principle in your project.
[Dependency inversion principle](https://en.wikipedia.org/wiki/Dependency_inversion_principle)  is a specific form of loosely coupling software modules.

In the following rules, written into `phparkitect.php` file into the root of a project, we said that:
```
* Domain layer should not depend on other namespaces
* Application layer should depend on itself and the domain layer. 
```

The code of our rules:

```
$rule_1 = Rule::allClasses()
        ->that(new ResideInOneOfTheseNamespaces('App\Domain'))
        ->should(new DependsOnlyOnTheseNamespaces('App\Domain'))
        ->because('we want to avoid that domain depends on other namespaces.');
        
$rule_2 = Rule::allClasses()
        ->that(new ResideInOneOfTheseNamespaces('App\Application'))
        ->should(new DependsOnlyOnTheseNamespaces('App\Application', 'App\Domain'))
        ->because('we want that application depends only on itself and domain namespace.');
```
