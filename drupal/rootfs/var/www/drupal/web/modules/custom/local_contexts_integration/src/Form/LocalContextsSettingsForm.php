<?php

namespace Drupal\local_contexts_integration\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

class LocalContextsSettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['local_contexts_integration.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'local_contexts_integration_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('local_contexts_integration.settings');
    $form['field_identifier'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Field Identifier'),
      '#default_value' => $config->get('field_local_context'),
      '#description' => $this->t('Enter the Field Identifier to use for API calls.'),
      '#required' => TRUE,
    ];


    $form['api_key'] = [
      '#type' => 'textfield',
      '#title' => $this->t('API Key'),
      '#default_value' => $config->get('api_key'),
      '#description' => $this->t('Enter API key for accessing Local Contexts.'),
      '#required' => TRUE,
    ];

    $form['api_base_url'] = [
      '#type' => 'select',
      '#title' => $this->t('API Base URL'),
      '#options' => [
        'https://localcontextshub.org/api/v2' => $this->t('https://localcontextshub.org/api/v2 - Live Instance of the Local Contexts Hub'),
        'https://sandbox.localcontextshub.org/api/v2/' => $this->t('https://sandbox.localcontextshub.org/api/v2/ - Sandbox/Testing site for the Local Contexts Hub API'),
      ],
      '#default_value' => $config->get('api_base_url') ?: 'https://localcontextshub.org/api/v2',
      '#description' => $this->t('Select the base URL for the API.'),
      '#required' => TRUE,
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $apiKey = $form_state->getValue('api_key');
    $field_identifier = $form_state->getValue('field_identifier');
    $apiBaseUrl = $form_state->getValue('api_base_url');

    // Validate the API key.
    if (!$this->validateApiKey($apiKey)) {
      $form_state->setErrorByName('api_key', $this->t('The API key is invalid.'));
      return;
    }

    // Validate the Project ID.
    if (!$this->validateFieldIdentifier($field_identifier)) {
      $form_state->setErrorByName('field_identifier', $this->t('The Field identifier is invalid.'));
      return;
    }

    // Validate the API Base URL.
    if (!$this->validateApiBaseUrl($apiBaseUrl)) {
      $form_state->setErrorByName('api_base_url', $this->t('The selected API Base URL is invalid.'));
      return;
    }

    // Save the configuration if validation passes.
    $this->config('local_contexts_integration.settings')
      ->set('api_key', $apiKey)
      ->set('field_identifier', $field_identifier)
      ->set('apiBaseUrl', $apiBaseUrl)
      ->save();

    parent::submitForm($form, $form_state);
  }

  /**
   * Validate the API key.
   *
   * @param string $apiKey
   *   The API key to validate.
   *
   * @return bool
   *   TRUE if the API key is valid, FALSE otherwise.
   */
  protected function validateApiKey($apiKey) {
    return !empty($apiKey); // Simplified validation.
  }

  /**
   * Validate the Project ID.
   *
   * @param string $field_identifier
   *   The Project ID to validate.
   *
   * @return bool
   *   TRUE if the Project ID is valid, FALSE otherwise.
   */
  protected function validateFieldIdentiier($field_identifier) {
    return !empty($field_identifier); // Simplified validation.
  }

  /**
   * Validate the API Base URL.
   *
   * @param string $apiBaseUrl
   *   The API Base URL to validate.
   *
   * @return bool
   *   TRUE if the API Base URL is valid, FALSE otherwise.
   */
  protected function validateApiBaseUrl($apiBaseUrl) {
    $validUrls = [
      'https://localcontextshub.org/api/v2', 
      'https://sandbox.localcontextshub.org/api/v2/'
    ];

    return in_array($apiBaseUrl, $validUrls); // Check if the selected URL is in the allowed list.
  }
}
