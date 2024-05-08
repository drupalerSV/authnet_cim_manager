<?php declare(strict_types = 1);

namespace Drupal\authnet_cim_manager\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Configure Authorise.Net CIM Manager settings for this site.
 */
final class SettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId(): string {
    return 'authnet_cim_manager_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames(): array {
    return ['authnet_cim_manager.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state): array {
    $form['api_id'] = [
      '#type' => 'textfield',
      '#title' => $this->t('API ID'),
      '#default_value' => $this->config('authnet_cim_manager.settings')->get('api_id'),
    ];
    $form['transaction_key'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Transaction Key'),
      '#default_value' => $this->config('authnet_cim_manager.settings')->get('transaction_key'),
    ];
    $form['environment'] = [
      '#type' => 'select',
      '#title' => $this->t('Environment'),
      '#options' => ['development'=>$this->t('Development'), 'production'=>$this->t('Production')],
      '#default_value' => $this->config('authnet_cim_manager.settings')->get('environment'),
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state): void {
    $this->config('authnet_cim_manager.settings')
      ->set('api_id', $form_state->getValue('api_id'))
      ->set('transaction_key', $form_state->getValue('transaction_key'))
      ->set('environment', $form_state->getValue('environment'))
      ->save();
    parent::submitForm($form, $form_state);
  }
}
