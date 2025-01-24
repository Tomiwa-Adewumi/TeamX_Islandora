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
   * @return array|null
   *   The API response data as an associative array, or NULL on failure.
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
    return NULL;
  }

  /**
   * Fetch project data using a specific node.
   *
   * @param \Drupal\node\Entity\Node $node
   *   The node entity.
   *
   * @return array|null
   *   The API response data as an associative array, or NULL on failure.
   */
  private function fetchProjectDataFromNode(Node $node) {
    $config = \Drupal::config('local_contexts_integration.settings');
    $field_identifier = $config->get('field_identifier');

    \Drupal::logger('local_contexts_integration')->notice("Field Identifier: $field_identifier");

    if (!$node->hasField($field_identifier)) {
      return [
        '#type' => 'markup',
        '#markup' => "Error: The field '$field_identifier' is not attached to this node.",
      ];
    }

    if ($node->get($field_identifier)->isEmpty()) {
      return [
        '#type' => 'markup',
        '#markup' => "Error: The field '$field_identifier' is empty.",
      ];
    }

    $project_id = $node->get($field_identifier)->value;
    \Drupal::logger('local_contexts_integration')->notice("Field '$field_identifier' value retrieved: $project_id");

    $project_data = $this->performApiCall($project_id);

    return $project_data
      ? [
        '#type' => 'markup',
        '#markup' => '<pre>' . print_r($project_data, TRUE) . '</pre>',
      ]
      : [
        '#type' => 'markup',
        '#markup' => 'Failed to fetch project data.',
      ];
  }

  /**
   * Perform the actual API call to fetch project data.
   *
   * @param string $project_id
   *   The project ID to fetch data for.
   *
   * @return array|null
   *   The API response data as an associative array, or NULL on failure.
   */
  private function performApiCall(string $project_id) {
    $config = \Drupal::config('local_contexts_integration.settings');
    $api_key = $config->get('api_key');
    $api_url = $config->get('api_url');

    if (empty($api_url) || empty($api_key)) {
      \Drupal::logger('local_contexts_integration')->error('API URL or API Key is not configured.');
      return NULL;
    }

    $url = $api_url . '/' . $project_id . '/';

    if (!filter_var($url, FILTER_VALIDATE_URL)) {
      \Drupal::logger('local_contexts_integration')->error('Invalid API URL: @url', ['@url' => $url]);
      return NULL;
    }

    try {
      $response = $this->http_client->get($url, [
        'headers' => [
          'X-Api-Key' => $api_key,
          'accept' => 'application/json',
        ],
      ]);
      return json_decode($response->getBody(), TRUE);
    } catch (\Exception $e) {
      \Drupal::logger('local_contexts_integration')->error('API Error: @message', ['@message' => $e->getMessage()]);
      return NULL;
    }
  }
}
