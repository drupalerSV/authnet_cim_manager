<?php declare(strict_types = 1);

namespace Drupal\authnet_cim_manager\Controller;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Messenger\MessengerInterface;
use net\authorize\api\contract\v1 as AnetAPI;
use net\authorize\api\controller as AnetController;

/**
 * Returns responses for Authorise.Net CIM Manager routes.
 */
final class CreateCimController extends ControllerBase {

  /**
   * The configuration factory.
   *
   * @var ConfigFactoryInterface
   */
  protected $configFactory;
  protected $messenger;

  /**
   * Constructs a new CreateCimController object.
   *
   * @param ConfigFactoryInterface $config_factory
   *   The configuration factory.
   * @param MessengerInterface $messenger
   *   The messenger service.
   */
  public function __construct(ConfigFactoryInterface $config_factory, MessengerInterface $messenger) {
    $this->configFactory = $config_factory;
    $this->messenger = $messenger;
  }

  /**
   * Builds the response.
   */

  public function createCustomerProfile($data): ?AnetAPI\AnetApiResponseType
  {
    // Get merchant ID and transaction key from configuration.
    $config = $this->configFactory->get('authnet_cim_manager.settings');
    $merchantId = $config->get('api_id');
    $transactionKey = $config->get('transaction_key');
    $environment = $config->get('environment');

    /* Create a merchantAuthenticationType object with authentication details
       retrieved from the constants file */
    $merchantAuthentication = new AnetAPI\MerchantAuthenticationType();
    $merchantAuthentication->setName($merchantId);
    $merchantAuthentication->setTransactionKey($transactionKey);

    // Set the transaction's refId.
    $refId = 'ref' . time();

    // Set credit card information for payment profile.
    $creditCard = new AnetAPI\CreditCardType();
    $creditCard->setCardNumber($data['card_number']);
    $creditCard->setExpirationDate($data['expiry_date']);
    $creditCard->setCardCode($data['cvv']);
    $paymentCreditCard = new AnetAPI\PaymentType();
    $paymentCreditCard->setCreditCard($creditCard);

    // Create the Bill To info for new payment type.
    $billTo = new AnetAPI\CustomerAddressType();
    $billTo->setFirstName($data['first_name']);
    $billTo->setLastName($data['last_name']);
    $billTo->setCompany($data['company']);
    $billTo->setAddress($data['address']);
    $billTo->setCity($data['city']);
    $billTo->setState($data['state']);
    $billTo->setZip($data['zip']);
    $billTo->setCountry($data['country']);
    $billTo->setPhoneNumber($data['phone']);

    // Create a new CustomerPaymentProfile object.
    $paymentProfile = new AnetAPI\CustomerPaymentProfileType();
    $paymentProfile->setCustomerType($data['customer_type']);
    $paymentProfile->setBillTo($billTo);
    $paymentProfile->setPayment($paymentCreditCard);
    $paymentProfiles[] = $paymentProfile;

    // Create a new CustomerProfileType and add the payment profile object.
    $customerProfile = new AnetAPI\CustomerProfileType();
    $customerProfile->setDescription("drupal submission");
    $customerProfile->setMerchantCustomerId("M_" . time());
    $customerProfile->setEmail($data['email']);
    $customerProfile->setpaymentProfiles($paymentProfiles);

    // Assemble the complete transaction request.
    $request = new AnetAPI\CreateCustomerProfileRequest();
    $request->setMerchantAuthentication($merchantAuthentication);
    $request->setRefId($refId);
    $request->setProfile($customerProfile);

    // Create the controller and get the response.
    $controller = new AnetController\CreateCustomerProfileController($request);
    if ($environment === 'production') {
      $response = $controller->executeWithApiResponse(\net\authorize\api\constants\ANetEnvironment::PRODUCTION);
    }else{
      $response = $controller->executeWithApiResponse(\net\authorize\api\constants\ANetEnvironment::SANDBOX);
    }
    if (($response != null) && ($response->getMessages()->getResultCode() == "Ok")){
      $profile_id = $response->getCustomerProfileId();
      $paymentProfiles = $response->getCustomerPaymentProfileIdList();
      $validate_payment = $this->validateCustomerPaymentProfile($profile_id, $paymentProfiles[0]);
      $resp = $validate_payment->getMessages()->getResultCode();
      if ($resp === 'Ok'){
        $this->messenger->addStatus("Successfully created customer profile : " . $profile_id);
      }
      else {
        $this->deleteCustomerProfile($profile_id);
        $message = $validate_payment->getMessages()->getMessage()[0]->getText();
        $this->messenger->addError($message);
      }
    }else{
      $message = $this->$response->getMessages()->getMessage()[0]->getText();
      $this->messenger->addError($message);
    }
    return $response;
  }

  private function validateCustomerPaymentProfile($customerProfileId, $customerPaymentProfileId): ?AnetAPI\AnetApiResponseType
  {
    $config = $this->configFactory->get('authnet_cim_manager.settings');
    $merchantId = $config->get('api_id');
    $transactionKey = $config->get('transaction_key');
    $environment = $config->get('environment');

    /* Create a merchantAuthenticationType object with authentication details
       retrieved from the constants file */
    $merchantAuthentication = new AnetAPI\MerchantAuthenticationType();
    $merchantAuthentication->setName($merchantId);
    $merchantAuthentication->setTransactionKey($transactionKey);

    //validation tests , does not send an email receipt.
    $validation = "liveMode";

    $request = new AnetAPI\ValidateCustomerPaymentProfileRequest();
    $request->setMerchantAuthentication($merchantAuthentication);
    $request->setCustomerProfileId($customerProfileId);
    $request->setCustomerPaymentProfileId($customerPaymentProfileId);
    $request->setValidationMode($validation);
    $controller = new AnetController\ValidateCustomerPaymentProfileController($request);
    if ($environment === 'production'){
      $response = $controller->executeWithApiResponse( \net\authorize\api\constants\ANetEnvironment::PRODUCTION);
    }else{
      $response = $controller->executeWithApiResponse( \net\authorize\api\constants\ANetEnvironment::SANDBOX);
    }
    return $response;
  }

  private function deleteCustomerProfile($customerProfileId): void
  {
    // get credentials from config.
    $config = $this->configFactory->get('authnet_cim_manager.settings');
    $merchantId = $config->get('api_id');
    $transactionKey = $config->get('transaction_key');
    $environment = $config->get('environment');

    /* Create a merchantAuthenticationType object with authentication details
       retrieved from the constants file */
    $merchantAuthentication = new AnetAPI\MerchantAuthenticationType();
    $merchantAuthentication->setName($merchantId);
    $merchantAuthentication->setTransactionKey($transactionKey);

    // Delete an existing customer profile.
    $request = new AnetAPI\DeleteCustomerProfileRequest();
    $request->setMerchantAuthentication($merchantAuthentication);
    $request->setCustomerProfileId( $customerProfileId );
    $controller = new AnetController\DeleteCustomerProfileController($request);
    if ($environment === 'production'){
      $controller->executeWithApiResponse( \net\authorize\api\constants\ANetEnvironment::PRODUCTION);
    }else{
      $controller->executeWithApiResponse( \net\authorize\api\constants\ANetEnvironment::SANDBOX);
    }
  }
}
