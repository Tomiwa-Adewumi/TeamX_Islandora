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

    $form['project_id'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Project ID'),
      '#default_value' => $config->get('project_id'),
      '#description' => $this->t('Enter the default Project ID to use for API calls.'),
      '#required' => TRUE,
    ];

    $form['api_key'] = [
      '#type' => 'textfield',
      '#title' => $this->t('API Key'),
      '#default_value' => $config->get('api_key'),
      '#description' => $this->t('Enter your API key for accessing Local Contexts.'),
      '#required' => TRUE,
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $apiKey = $form_state->getValue('api_key');
    $projectId = $form_state->getValue('project_id');

    // Validate the API key.
    if (!$this->validateApiKey($apiKey)) {
      $form_state->setErrorByName('api_key', $this->t('The API key is invalid.'));
      return;
    }

    // Validate the Project ID.
    if (!$this->validateProjectId($projectId)) {
      $form_state->setErrorByName('project_id', $this->t('The Project ID is invalid.'));
      return;
    }

    // Save the configuration if validation passes.
    $this->config('local_contexts_integration.settings')
      ->set('api_key', $apiKey)
      ->set('project_id', $projectId)
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
   * @param string $projectId
   *   The Project ID to validate.
   *
   * @return bool
   *   TRUE if the Project ID is valid, FALSE otherwise.
   */
  protected function validateProjectId($projectId) {
    
    return !empty($projectId); // Simplified validation.
  }
}
