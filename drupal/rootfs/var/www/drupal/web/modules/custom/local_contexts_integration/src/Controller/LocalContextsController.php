<?php

namespace Drupal\local_contexts_integration\Controller;

use Drupal\Core\Controller\ControllerBase;
use GuzzleHttp\Client;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\node\Entity\Node;

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
   * The Route Match service for accessing route parameters.
   *
   * @var \Drupal\Core\Routing\RouteMatchInterface
   */
  protected $route_match;

  /**
   * Constructs the LocalContextsController object.
   *
   * @param \GuzzleHttp\Client $http_client
   *   The HTTP client.
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *   The Route Match service.
   */
  public function __construct(Client $http_client, RouteMatchInterface $route_match) {
    $this->http_client = $http_client;
    $this->route_match = $route_match;
  }

  /**
   * Dependency injection for services.
   *
   * @param \Symfony\Component\DependencyInjection\ContainerInterface $container
   *   The service container.
   *
   * @return static
   *   An instance of the LocalContextsController.
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('http_client'),
      $container->get('current_route_match')
    );
  }

  /**
  * Fetch project data, handling the node retrieval from the route.
  *
  * @return array
  *   The processed API response as a structured array, or an empty array on failure.
  */
  public function fetchProjectData() {
    $node = $this->route_match->getParameter('node');

    if ($node instanceof Node) {
      return $this->fetchProjectDataFromNode($node);
    }

    $message = $node === NULL
      ? 'No node found in the current route.'
      : 'The route parameter is not a valid Node entity.';

    \Drupal::logger('local_contexts_integration')->warning($message);
    return [];
  }


  /**
   * Fetch project data using a specific node.
   *
   * @param \Drupal\node\Entity\Node $node
   *   The node entity.
   *
   * @return array
   *   The structured response data.
   */
  private function fetchProjectDataFromNode(Node $node) {
    $config = \Drupal::config('local_contexts_integration.settings');
    $field_identifier = $config->get('field_identifier');

    if (!$node->hasField($field_identifier) || $node->get($field_identifier)->isEmpty()) {
      \Drupal::logger('local_contexts_integration')->warning("Field '$field_identifier' is missing or empty on node ID: {$node->id()}");
      return [];
    }

    $project_id = $node->get($field_identifier)->value;
    $raw_data = $this->performApiCall($project_id);

    // Filter and structure the API response.
    return $this->filterApiResponse($raw_data);
  }

  
  /**
   * Filter the API response to keep only specified fields.
   *
   * @param array $data
   *   The original API response data.
   *
   * @return array
   *   The filtered response data.
   */
  private function filterApiResponse(array $data) {
    return [
      'unique_id' => $data['unique_id'] ?? null,
      'title' => $data['title'] ?? null,
      'date_added' => $data['date_added'] ?? null,
      'date_modified' => $data['date_modified'] ?? null,
      'tk_labels' => $data['tk_labels'] ?? [],
    ];
  }


  /**
   * Perform the actual API call to fetch project data.
   *
   * @param string $project_id
   *   The project ID to fetch data for.
   *
   * @return array
   *   The raw API response data as an associative array, or an empty array on failure.
   */
  private function performApiCall(string $project_id) {
    $config = \Drupal::config('local_contexts_integration.settings');
    $api_key = $config->get('api_key');
    $api_url = $config->get('api_url');

    if (empty($api_url) || empty($api_key)) {
      \Drupal::logger('local_contexts_integration')->error('API URL or API Key is not configured.');
      return [];
    }

    $url = $api_url . '/' . $project_id . '/';

    if (!filter_var($url, FILTER_VALIDATE_URL)) {
      \Drupal::logger('local_contexts_integration')->error('Invalid API URL: @url', ['@url' => $url]);
      return [];
    }

    try {
      $response = $this->http_client->get($url, [
        'headers' => [
          'X-Api-Key' => $api_key,
          'accept' => 'application/json',
        ],
      ]);

      // Decode the JSON response into an associative array.
      $data = json_decode($response->getBody(), TRUE);


      return is_array($data) ? $data : [];
    } catch (\Exception $e) {
      \Drupal::logger('local_contexts_integration')->error('API Error: @message', ['@message' => $e->getMessage()]);
      return [];
    }
  }

}
