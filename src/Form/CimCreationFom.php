<?php declare(strict_types = 1);

namespace Drupal\authnet_cim_manager\Form;

use Drupal\authnet_cim_manager\Controller\CreateCimController;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Messenger\MessengerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides an Authorise.Net CIM Manager form.
 */
final class CimCreationFom extends FormBase {

  protected $configFactory;
  protected $messenger;

  public function __construct(ConfigFactoryInterface $configFactory, MessengerInterface $messenger) {
    $this->configFactory = $configFactory;
    $this->messenger = $messenger;
  }

  public static function create(ContainerInterface $container): CimCreationFom
  {
    return new self(
      $container->get('config.factory'),
      $container->get('messenger')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId(): string {
    return 'authnet_cim_manager_cim_creation_fom';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state): array {
    $form['#attached']['library'][] = 'authnet_cim_manager/authorize_net_css';
    $form['heading_title'] = [
      '#markup' => $this->t('<h3 class="form-cim-creation">Cim Creation Form</h3>'),
    ];
    $form['row_1'] = [
      '#type' => 'container',
      '#attributes' => [
        'class' => ['flex-container'],
      ]
    ];
    $form['row_1']['customer_type'] = [
      '#type' => 'select',
      '#title' => $this->t('Customer Type'),
      '#options' => [''=>$this->t('-Select-'),'individual'=>$this->t('Individual'), 'business'=> $this->t('Business')],
    ];
    $form['row_1']['company'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Company'),
      '#placeholder' => $this->t('Company Name'),
      '#required' => FALSE,
    ];
    $form['flex_card_container'] = [
      '#type' => 'container',
      '#attributes' => ['class' => ['field-card-container']],
    ];
    $form['flex_card_container']['card_number'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Credit Card Number'),
      '#placeholder' => '1234 1234 1234 1234',
      '#required' => TRUE,
    ];
    $form['flex_card_container']['expiry_date'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Expiry Date'),
      '#placeholder' => 'mm/yy',
      '#required' => TRUE,
    ];
    $form['flex_card_container']['cvv'] = [
      '#type' => 'textfield',
      '#title' => $this->t('CVV'),
      '#required' => TRUE,
    ];
    $form['flex_container'] = [
      '#type' => 'container',
      '#attributes' => ['class' => ['field-auth-flex']],
    ];
    $form['flex_container']['first_name'] = [
      '#type' => 'textfield',
      '#title' => $this->t('First Name'),
      '#placeholder' => $this->t('First Name'),
      '#required' => TRUE,
    ];
    $form['flex_container']['last_name'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Last Name'),
      '#placeholder' => $this->t('Last Name'),
      '#required' => TRUE,
    ];
    $form['flex_container']['email'] = [
      '#type' => 'email',
      '#title' => $this->t('Email'),
      '#placeholder' => $this->t('abc@example.com'),
      '#required' => TRUE,
    ];
    $form['flex_container']['phone'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Phone'),
      '#placeholder' => $this->t('Phone number with country code'),
      '#required' => TRUE,
    ];
    $form['flex_container']['address'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Address'),
      '#placeholder' => $this->t('New Address'),
      '#required' => FALSE,
    ];
    $form['flex_container']['city'] = [
      '#type' => 'textfield',
      '#title' => $this->t('City'),
      '#placeholder' => $this->t('City'),
      '#required' => FALSE,
    ];
    $form['flex_container']['state'] = [
      '#type' => 'textfield',
      '#title' => $this->t('State'),
      '#placeholder' => $this->t('State'),
      '#required' => FALSE,
    ];
    $form['flex_container']['country'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Country'),
      '#placeholder' => $this->t('Country'),
      '#required' => FALSE,
    ];
    $form['flex_container']['zip'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Zip'),
      '#required' => FALSE,
    ];
    $form['actions'] = [
      '#type' => 'actions',
      'submit' => [
        '#type' => 'submit',
        '#value' => $this->t('Submit'),
      ],
    ];
    $form['#attached']['library'][] = 'authnet_cim_manager/authorize_net_js';
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state): void {

    // Validate Credit Card Number
    $card_number = $form_state->getValue('card_number');
    $card_number_without_spaces = str_replace(' ', '', $card_number);
    if (!ctype_digit($card_number_without_spaces)) {
      $form_state->setErrorByName('card_number', $this->t('Card Number must contain only digits.'));
    }

    // validate expiry date format.
    $expiry_date = $form_state->getValue('expiry_date');
    if (!preg_match('/^(0[1-9]|1[0-2])\/\d{2}$/', $expiry_date)) {
      $form_state->setErrorByName('expiry_date', $this->t('Expiry date must be in the format mm/yy.'));
    }

    // expiry date validation.
    list($exp_month, $exp_year) = explode('/', $expiry_date);
    $current_month = date('m');
    $firstTwoDigits = substr(date("Y"), 0, 2);
    $exp_year_full = $firstTwoDigits . $exp_year;
    if ($exp_year_full < date('Y') || ($exp_year_full == date('Y') && $exp_month < $current_month)) {
      $form_state->setErrorByName('expiry_date', $this->t('Expiry date cannot be earlier than the current date.'));
    }
    // validate cvv.
    $cvv = $form_state->getValue('cvv');
    if (!ctype_digit($cvv)) {
      $form_state->setErrorByName('cvv', $this->t('CVV must contain only digits.'));
    }

    //phone number validation.
    $phone = $form_state->getValue('phone');

    // Regular expression to allow numbers, plus sign, hyphen, and parentheses
    if (!preg_match('/^[0-9()+\- ]*$/', $phone)) {
      $form_state->setErrorByName('phone', $this->t('Phone number can only contain numbers, +, -, parentheses, and space.'));
    }


    // validate zip code.
    $zip = $form_state->getValue('zip');
    if (!ctype_digit($zip)) {
      $form_state->setErrorByName('zip', $this->t('Zip code must contains only digits.'));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state): void {
    $card_number = $form_state->getValue('card_number');
    $expiry_date = $form_state->getValue('expiry_date');
    list($month, $year) = explode('/', $expiry_date);
    $firstTwoDigits = substr(date("Y"), 0, 2);
    $full_year =$firstTwoDigits.$year;
    $data = [
      'customer_type' => $form_state->getValue('customer_type'),
      'company' => $form_state->getValue('company'),
      'card_number' => str_replace(' ', '', $card_number),
      'expiry_date' => $full_year.'-'.$month,
      'cvv' => $form_state->getValue('cvv'),
      'first_name' => $form_state->getValue('first_name'),
      'last_name' => $form_state->getValue('last_name'),
      'email' => $form_state->getValue('email'),
      'phone' => $form_state->getValue('phone'),
      'address' => $form_state->getValue('address'),
      'city' => $form_state->getValue('city'),
      'state' => $form_state->getValue('state'),
      'country' => $form_state->getValue('country'),
      'zip' => $form_state->getValue('zip')
    ];
    $objCim = new CreateCimController($this->configFactory, $this->messenger);
    $objCim->createCustomerProfile($data);
  }
}
