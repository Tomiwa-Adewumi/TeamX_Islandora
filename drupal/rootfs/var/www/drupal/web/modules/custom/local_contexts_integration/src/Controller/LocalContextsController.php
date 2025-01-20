<?php

namespace Drupal\local_contexts_integration\Controller;

use Drupal\Core\Controller\ControllerBase;
use GuzzleHttp\Client;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Handles API calls to Local Contexts.
 */
class LocalContextsController extends ControllerBase {
  /**
   * The HTTP client for making API requests.
   *
   * @var \GuzzleHttp\Client
   */
  protected $http_client;

  /**
   * Constructs the LocalContextsController object.
   *
   * @param \GuzzleHttp\Client $http_client
   *   The HTTP client.
   */
  public function __construct(Client $http_client) {
    $this->http_client = $http_client;
  }

  /**
   * Dependency injection for the HTTP client.
   *
   * @param \Symfony\Component\DependencyInjection\ContainerInterface $container
   *   The service container.
   *
   * @return static
   *   An instance of the LocalContextsController.
   */
  public static function create(ContainerInterface $container) {
    return new static($container->get('http_client'));
  }

  /**
   * Fetch data from the Local Contexts API.
   *
   * @return array|null
   *   The API response data as an associative array, or NULL on failure.
   */
  public function fetchProjectData() {
    // Get the API key and project ID from configuration.
    $config = \Drupal::config('local_contexts_integration.settings');
    $api_key = $config->get('api_key');
    $project_id = $config->get('project_id');

    // Get the API key and project ID from configuration.
    // $config = \Drupal::config('local_contexts_integration.settings');
    // $api_key = "SGRhUk45RXYuRlV5NVFydEt2M0l5V2lXY0pqcGxGZGdvQ280Nk9NWlA";
    // $project_id = "452cb2ab-f26d-46c5-ac96-4fddad717286";





    // Build the API endpoint URL.
    $url = 'https://sandbox.localcontextshub.org/api/v2/projects/' . $project_id . '/';

    try {
      // Perform the API call with the updated header.
      $response = $this->http_client->get($url, [
        'headers' => [
          'X-Api-Key' => $api_key,
          'accept' => 'application/json',
        ],
      ]);

      // Decode and return the JSON response.
      return json_decode($response->getBody(), TRUE);
    } catch (\Exception $e) {
      // Log any errors that occur.
      \Drupal::logger('local_contexts_integration')->error('API Error: @message', ['@message' => $e->getMessage()]);
      return NULL;
    }
  }

  /**
   * Test fetching data from the Local Contexts API.
   *
   * @return array
   *   A render array displaying the fetched data or an error message.
   */
  public function testFetchProjectData() {
    // Call the fetchProjectData method.
    $data = $this->fetchProjectData();

    // Display the output or an error message.
    if ($data) {
      return [
        '#type' => 'markup',
        '#markup' => '<pre>' . print_r($data, TRUE) . '</pre>',
      ];
    }
    else {
      return [
        '#type' => 'markup',
        '#markup' => '<p>No data fetched. Check logs for errors.</p>',
      ];
    }
  }
}
