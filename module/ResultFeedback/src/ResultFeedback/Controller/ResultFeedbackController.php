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
namespace ResultFeedback\Controller;

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
class ResultFeedbackController extends \VuFind\Controller\AbstractBase
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
        $translator = $this->serviceLocator->get('Zend\Mvc\I18n\Translator');

        $view = $this->createViewModel();
        $view->useRecaptcha = $this->recaptcha()->active('feedback');
        $view->name = $this->params()->fromPost('name');
        $view->email = $this->params()->fromPost('email');
        $view->comments = $this->params()->fromPost('comments');
        $view->usertype = $this->params()->fromPost('usertype');
        $view->recordid = $this->params()->fromPost('recordid');
        $view->recordtitle = $this->params()->fromPost('recordtitle');

        $id = $this->params()->fromRoute('id', $this->params()->fromQuery('id'));
        $view->id = $id;
        $searchClassId = $this->params()->fromRoute('searchclassid', $this->params()->fromQuery('searchclassid'));
        $view->searchClassId = $searchClassId;
        $recordLoader = $this->serviceLocator->get('VuFind\Record\Loader');;
        $driver = $recordLoader->load($id, $searchClassId, false);
        $view->driver = $driver;

        $resultFeedbackConfig = $this->serviceLocator->get('VuFind\Config\PluginManager')->get('resultFeedback')->toArray();
        $resultUserTypes = [];
        if (isset($resultFeedbackConfig['resultFeedback']['user_types'])) {
            $resultUserTypes = $resultFeedbackConfig['resultFeedback']['user_types'];
        }
        $view->resultUserTypes = $resultUserTypes;

        $userIsInLocalNetwork = false;
        if (isset($resultFeedbackConfig['resultFeedback']['ip_local_network'])) {
            foreach ($resultFeedbackConfig['resultFeedback']['ip_local_network'] as $ip_local_network) {
                if ($this->startsWith($_SERVER['REMOTE_ADDR'], $ip_local_network)) {
                    $userIsInLocalNetwork = true;
                }
            }
        }
        $view->userIsInLocalNetwork = $userIsInLocalNetwork;

        // Process form submission:
        $view->hideForm = false;
        if ($this->formWasSubmitted('submit', $view->useRecaptcha)) {
            if (empty($view->email) || empty($view->comments)) {
                $this->flashMessenger()->addMessage('bulk_error_missing', 'error');
                return;
            }

            $recipient_email = isset($resultFeedbackConfig['resultFeedback']['recipient_email']) ? $resultFeedbackConfig['resultFeedback']['recipient_email'] : null;
            $recipient_name = isset($resultFeedbackConfig['resultFeedback']['recipient_name']) ? $resultFeedbackConfig['resultFeedback']['recipient_name'] : 'Your Library';
            $email_subject = isset($resultFeedbackConfig['resultFeedback']['email_subject']) ? $resultFeedbackConfig['resultFeedback']['email_subject'] : 'Result Feedback';
            $sender_email = isset($resultFeedbackConfig['resultFeedback']['sender_email']) ? $resultFeedbackConfig['resultFeedback']['sender_email'] : 'noreply@vufind.org';
            $sender_name = isset($resultFeedbackConfig['resultFeedback']['sender_name']) ? $resultFeedbackConfig['resultFeedback']['sender_name'] : 'Result Feedback';
            if ($recipient_email == null) {
                throw new \Exception(
                    'Result Feedback Module Error: Recipient Email Unset (see resultFeedback.ini)'
                );
            }

            if(!empty($view->usertype)){
                $email_message = $translator->translate('resultfeedback_usertype') . ':' . "\n" . $translator->translate($view->usertype) . "\n\n";
            } else {
                $email_message = '';
            }
            $email_message .= empty($view->name) ? '' : 'Name:' . "\n" . $view->name . "\n\n";
            $email_message .= $translator->translate('Email') . ':' . "\n" . $view->email . "\n\n";
            $email_message .= $translator->translate('PPN') . ':' . "\n" . $view->recordid . "\n\n";
            $email_message .= $translator->translate('Title') . ':' . "\n" . $view->recordtitle . "\n\n";

            if (isset($resultFeedbackConfig['resultFeedback']['base_url_' . $searchClassId])) {
              $email_message .= $translator->translate('Link') . ':' . "\n" . trim($resultFeedbackConfig['resultFeedback']['base_url_' . $searchClassId], '/') . '/' . $driver->getUniqueID() . "\n\n";
            }

            $email_message .= $translator->translate('Message') . ':' . "\n" . $view->comments . "\n\n";

            // This sets up the email to be sent
            // Attempt to send the email and show an appropriate flash message:

            $replyTo = $view->email;
            if (isset($resultFeedbackConfig['resultFeedback']['set_user_email_as_reply_to']) && !$resultFeedbackConfig['resultFeedback']['set_user_email_as_reply_to']) {
                $replyTo = null;
            }

            $cc = null;
            if (isset($resultFeedbackConfig['resultFeedback']['set_user_email_as_cc']) && $resultFeedbackConfig['resultFeedback']['set_user_email_as_cc']) {
                $cc = $view->email;
            }

            try {
                $mailer = $this->serviceLocator->get('VuFind\Mailer\Mailer');
                $mailer->send(
                    new Address($recipient_email, $recipient_name),
                    new Address($sender_email, $sender_name),
                    $email_subject,
                    $email_message,
                    $cc,
                    $replyTo
                );
                $this->flashMessenger()->addMessage(
                    'Your result feedback has been send', 'success'
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
}
