# GetYourGuide Challenge

## Before start

I usually prefer to use docker, that's why I decided to create a docker image, so to build it type:
```
docker build -t getyourguid/available-products .
``` 

But if you don't want to use it, you'll need to have [Composer](https://getcomposer.org/download/) and PHP installed in your machine.
To install all dependencies, type: 
```
composer install
```

## Running

Docker:
```
docker run --rm -it getyourguid/available-products --help
```

In your machine:
```
./available-products.php --help
```
Or if you prefer:
```
php solution.php --help
```

## Justifications

- Don't know if I need to talk about **composer**, but I choose it as dependency manager because is the most popular and easiest way to start a project in PHP.
- I used **Symfony Console** component just because for me is also the easiest way to create a command line.
- I could use curl directly but I decided to go for **Guzzle** just a simple library to deal with HTTP requests. 
- The name of command line is `available-products.php` but I created a shortcut to `solution.php` to be able you to run exacyly the same command that you asked in the task description.
- I also created a Docker image because I can be isolated from client machine, all the dependencies will be inside of docker.
- Regarding the implementation I believe for this test is fine, but the complexity of my algorithm is not good, I need to loop several times over products (to filter, sort by product_id, sort by start_time, etc.). I'll write more into next steps.

## Next steps

With more time I'd like to understand better the problem and know if the amount of data returned from API tends to grow, I think it's important even try to understand in average how many result client use to get, because it's not a lot it's fine otherwise I think I should improve the algorithm.  
