<?php

namespace GetYourGuide\Command;

use GuzzleHttp\Client;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class AvailableProductsCommand extends Command
{
    const DATE_TIME_FORMAT = 'Y-m-d\\TH:i';

    /**
     * @var \DateTime
     */
    protected $startTime;

    /**
     * @var \DateTime
     */
    protected $endTime;

    /**
     * @var int
     */
    protected $numberTravelers;

    protected function configure()
    {
        $this->setName('available-products')
            ->setDescription('Available products for specific date')
            ->setHelp('This command allows you to get list of available products to your trip based on your dates and number of travelers with you.')
            ->addArgument('api-endpoint', InputArgument::REQUIRED, 'API endpoint to search for products')
            ->addArgument('start-time', InputArgument::REQUIRED, sprintf('Start date and time of your trip (Format: %s)', self::DATE_TIME_FORMAT))
            ->addArgument('end-time', InputArgument::REQUIRED, sprintf('End date and time of your trip (Format: %s)', self::DATE_TIME_FORMAT))
            ->addArgument('number-travelers', InputArgument::REQUIRED, 'Number of travelers with you (max 30)')
        ;
    }

    protected function interact(InputInterface $input, OutputInterface $output)
    {
        $this->startTime = \DateTime::createFromFormat(self::DATE_TIME_FORMAT, $input->getArgument('start-time'));
        if (!$this->startTime) {
            $errors = \DateTime::getLastErrors();
            throw new \Exception('Start date is not a valid: ' . implode(' / ', $errors['errors']));
        }

        $this->endTime = \DateTime::createFromFormat(self::DATE_TIME_FORMAT, $input->getArgument('end-time'));
        if (!$this->endTime) {
            $errors = \DateTime::getLastErrors();
            throw new \Exception('End date is not a valid: ' . implode(' / ', $errors['errors']));
        }

        $this->numberTravelers = $input->getArgument('number-travelers');
        if ($this->numberTravelers < 1 || $this->numberTravelers > 30) {
            throw new \Exception(sprintf('Number of travelers need to be between 1 and 30, it was received "%s"', $this->numberTravelers));
        }
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        if (!$input->isInteractive()) {
            // If you don't pass -it to docker run, it'll not check parameters, so let's force always check them
            $this->interact($input, $output);
        }

        $httpClient = new Client();
        // In case of invalid URL or if it was not possible access the endpoint, it'll throw an exception
        // but it's ok since, these exceptions are in general clear what's going on
        $response = $httpClient->get($input->getArgument('api-endpoint'));
        if ($response->getStatusCode() !== 200) {
            throw new \Exception(sprintf('An error occurred communicating with API endpoint: %s %s', $response->getStatusCode(), $response->getReasonPhrase()));
        }
        if (!in_array('application/json', $response->getHeader('Content-Type'))) {
            // Should I do something?
        }

        $body = $response->getBody()->getContents();
        $result = \GuzzleHttp\json_decode($body, true);

        $products = [];
        if (array_key_exists('product_availabilities', $result)) {
            $products = $this->filter($result['product_availabilities'], $this->startTime, $this->endTime, $this->numberTravelers);
        }

        $output->writeln(json_encode($products));
    }

    protected function filter(array $results = [], \DateTIme $startTime, \DateTIme $endData, $numberTravelers)
    {
        $products = [];

        // filter and group start time into $products
        foreach ($results as $product) {
            if (!isset($product['product_id'], $product['activity_start_datetime'], $product['activity_duration_in_minutes'], $product['places_available'])) {
                continue;
            }

            // Abort as soon as possible, $numberTravelers is a primary type so let's check first
            if ($product['places_available'] < $numberTravelers) {
                continue;
            }

            $activityStartTime = \DateTime::createFromFormat(self::DATE_TIME_FORMAT, $product['activity_start_datetime']);
            if ($activityStartTime < $startTime) {
                continue;
            }

            $activityEndTime = clone $activityStartTime;
            $activityEndTime->add(new \DateInterval(sprintf('PT%sM', $product['activity_duration_in_minutes'])));
            if ($activityEndTime > $endData) {
                continue;
            }

            $productId = $product['product_id'];
            if (!isset($products[$productId])) {
                $products[$productId] = [
                    'product_id' => $productId,
                    'available_starttimes' => [],
                ];
            }
            $products[$productId]['available_starttimes'][] = $activityStartTime;
        }

        // Sort product_id
        ksort($products);

        // Sort start time
        array_walk($products, function (&$product) {
            usort($product['available_starttimes'], function ($a, $b) {
                return $a > $b;
            });
            $product['available_starttimes'] = array_map(function (\DateTime $startTime) {
                return $startTime->format(self::DATE_TIME_FORMAT);
            }, $product['available_starttimes']);
        });

        // We used product_id as key to be faster insert start time and sort, now we don't need anymore
        return array_values($products);
    }
}
