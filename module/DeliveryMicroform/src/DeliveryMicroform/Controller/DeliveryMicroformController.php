<?php
/**
 * Feedback Controller
 *
 * PHP version 7
 *
 * @category VuFind
 * @package  Controller
 * @author   Johannes Schultze <schultze@effective-webwork.de>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org Main Site
 */
namespace DeliveryMicroform\Controller;

use VuFind\Exception\Mail as MailException;
use Zend\Mail\Address;

/**
 * Feedback Class
 *
 * Controls the Feedback
 *
 * @category VuFind
 * @package  Controller
 * @author   Johannes Schultze <schultze@effective-webwork.de>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org/wiki/development Wiki
 */
class DeliveryMicroformController extends \VuFind\Controller\AbstractBase
{
    /**
     * Display Feedback home form.
     *
     * @return \Zend\View\Model\ViewModel
     */
    public function homeAction()
    {
        return $this->forwardTo('ResultFeedback', 'Email');
    }

    /**
     * Receives input from the user and sends an email to the recipient set in
     * the resultFeedback.ini
     *
     * @return void
     */
    public function emailAction()
    {
        $account = $this->getAuthManager();
        if ($account->isLoggedIn() == false) {
            return $this->forceLogin();
        }

        $config = $this->serviceLocator->get('VuFind\Config\PluginManager')->get('DeliveryMicroform');
        $translator = $this->serviceLocator->get('Zend\Mvc\I18n\Translator');

        $view = $this->createViewModel();

        $id = $this->params()->fromRoute('id', $this->params()->fromQuery('id'));
        $view->id = $id;

        $searchclassid = $this->params()->fromRoute('id', $this->params()->fromQuery('searchclassid'));
        $view->searchclassid = $searchclassid;

        $recordLoader = $this->serviceLocator->get('VuFind\Record\Loader');;
        $driver = $recordLoader->load($id, $searchclassid, false);

        $view->title = $this->getMarcValue($driver, '245|a');

        $callnumbers = $this->getMarcValues($driver, '980');
        foreach ($callnumbers as $callnumber) {
            if  ($callnumber->getSubField('2')->getData() == $config['DeliveryMicroform']['callnumber_iln']) {
                $view->callnumber = $callnumber->getSubField('d')->getData();
            }
        }

        $view->date_order = date('d.m.Y');

        $user = $this->getUser();

        $view->userName = $user->username;
        $view->userFullname = $user->firstname.' '.$user->lastname;

        $yearDate = $this->getFormValue('deliverymicroform_year_date');
        $view->year_date = $yearDate;

        $userEmail = $this->getFormValue('deliverymicroform_email');
        if (empty($userEmail)) {
            $userEmail = $user->email;
        }
        $view->userEmail = $userEmail;

        $dateTime = $this->getFormValue('deliverymicroform_date_time');
        $view->date_time = $dateTime;

        $deliverymicroformIntroduction = $this->getFormValue('deliverymicroform_introduction');
        $view->deliverymicroform_introduction = $deliverymicroformIntroduction;

        $view->comments = $this->params()->fromPost('comments');

        $view->hideForm = false;
        if ($this->formWasSubmitted('submit', $view->useRecaptcha)) {
            $error = false;
            if (empty($yearDate)) {
                $this->flashMessenger()->addMessage('bulk_error_missing', 'error');
                $view->deliverymicroform_year_date_error = true;
                $error = true;
            }
            if (empty($userEmail)) {
                $this->flashMessenger()->addMessage('bulk_error_missing', 'error');
                $view->deliverymicroform_email_error = true;
                $error = true;
            }
            if (empty($dateTime)) {
                $this->flashMessenger()->addMessage('bulk_error_missing', 'error');
                $view->deliverymicroform_date_time_error = true;
                $error = true;
            }
            if (empty($deliverymicroformIntroduction)) {
                $this->flashMessenger()->addMessage('bulk_error_missing', 'error');
                $view->deliverymicroform_introduction_error = true;
                $error = true;
            }

            if ($error) {
                return $view;
            }

            $recipient_email = isset($config['DeliveryMicroform']['recipient_email']) ? $config['DeliveryMicroform']['recipient_email'] : null;
            if ($recipient_email == null) {
                throw new \Exception(
                    'Result Feedback Module Error: Recipient Email Unset (see DeliveryMicroform.ini)'
                );
            }

            $recipient_name = isset($config['DeliveryMicroform']['recipient_name']) ? $config['DeliveryMicroform']['recipient_name'] : '';

            $recipient_email_subject = isset($config['DeliveryMicroform']['email_subject']) ? $config['DeliveryMicroform']['email_subject'] : null;
            if ($recipient_email_subject == null) {
                throw new \Exception(
                    'Result Feedback Module Error: Recipient Email Subject Unset (see DeliveryMicroform.ini)'
                );
            }

            $sender_email = isset($config['DeliveryMicroform']['sender_email']) ? $config['DeliveryMicroform']['sender_email'] : null;
            if ($sender_email == null) {
                throw new \Exception(
                    'Result Feedback Module Error: Sender Email Unset (see DeliveryMicroform.ini)'
                );
            }
            $sender_name = isset($config['DeliveryMicroform']['sender_name']) ? $config['DeliveryMicroform']['sender_name'] : '';

            $email_message = '';
            $email_message .= $translator->translate('PPN') . ': ' . $this->getFormValue('recordid') . "\n";
            $email_message .= $translator->translate('DeliveryMicroform Title') . ': ' . $this->getFormValue('deliverymicroform_title') . "\n";
            $email_message .= $translator->translate('DeliveryMicroform Callnumber') . ': ' . $this->getFormValue('deliverymicroform_callnumber') . "\n";
            $email_message .= $translator->translate('DeliveryMicroform ID') . ': ' . $this->getFormValue('deliverymicroform_id') . "\n";
            $email_message .= $translator->translate('DeliveryMicroform Date Order') . ': ' . $this->getFormValue('deliverymicroform_date_order') . "\n";
            $email_message .= $translator->translate('DeliveryMicroform Date Time') . ': ' . $this->getFormValue('deliverymicroform_date_time') . "\n";
            $email_message .= $translator->translate('DeliveryMicroform Introduction') . ': ' . $this->getFormValue('deliverymicroform_introduction') . "\n";

            $email_message .= "\n";

            $email_message .= $translator->translate('Patron') . ': ' . $view->userName . "\n";
            $email_message .= $translator->translate('Fullname') . ': ' . $view->userFullname . "\n";
            $email_message .= $translator->translate('E-Mail') . ': ' . $view->userEmail . "\n";

            $email_message .= "\n";
            $email_message .= $translator->translate('Comments') . ': ' . $view->comments . "\n\n";

            // This sets up the email to be sent
            // Attempt to send the email and show an appropriate flash message:
            try {
                $mailer = $this->serviceLocator->get('VuFind\Mailer\Mailer');
                $mailer->send(
                    new Address($recipient_email, $recipient_name),
                    new Address($sender_email, $sender_name),
                    $recipient_email_subject,
                    $email_message,
                    null,
                    $view->email
                );

                $formConfirm = '';
                if (isset($config['DeliveryMicroform']['formConfirm'])) {
                    $formConfirm = $config['DeliveryMicroform']['formConfirm'];
                }
                $this->flashMessenger()->addMessage(
                    $formConfirm, 'success'
                );
                $view->hideForm = true;
            } catch (MailException $e) {
                $this->flashMessenger()->addMessage($e->getMessage(), 'error');
            }
        }

        return $view;
    }

    private function startsWith($haystack, $needle) {
      return substr($haystack, 0, strlen($needle)) === $needle;
    }

    private function getMarcValue($driver, $marcField) {
        $marcFieldArray = explode('|', $marcField);
        if (sizeof($marcFieldArray) == 2) {
            return $driver->getMarcRecord()->getField($marcFieldArray[0])->getSubField($marcFieldArray[1])->getData();
        }
        return '';
    }

    private function getMarcValues($driver, $marcField) {
        return $driver->getMarcRecord()->getFields($marcField);
    }

    private function getFormValue($parameter) {
        return $this->params()->fromRoute($parameter, $this->params()->fromQuery($parameter, $this->params()->fromPost($parameter)));
    }
}
