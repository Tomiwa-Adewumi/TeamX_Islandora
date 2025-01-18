<?php

namespace Drupal\local_contexts_integration\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

class LocalContextsSettingsForm extends ConfigFormBase {

  protected function getEditableConfigNames() {
    return ['local_contexts_integration.settings'];
  }

  public function getFormId() {
    return 'local_contexts_integration_settings_form';
  }

  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('local_contexts_integration.settings');

    $form['project_id'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Project ID'),
      '#default_value' => $config->get('project_id'),
    ];

    $form['api_key'] = [
      '#type' => 'textfield',
      '#title' => $this->t('API Key'),
      '#default_value' => $config->get('api_key'),
    ];

    return parent::buildForm($form, $form_state);
  }

  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->config('local_contexts_integration.settings')
      ->set('project_id', $form_state->getValue('project_id'))
      ->set('api_key', $form_state->getValue('api_key'))
      ->save();
    parent::submitForm($form, $form_state);
  }
}
