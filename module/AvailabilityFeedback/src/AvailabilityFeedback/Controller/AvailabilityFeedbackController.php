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
namespace AvailabilityFeedback\Controller;

use VuFind\Exception\Mail as MailException;
use Laminas\Mail\Address;

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
class AvailabilityFeedbackController extends \VuFind\Controller\AbstractBase
{
    /**
     * Display Feedback home form.
     *
     * @return \Laminas\View\Model\ViewModel
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

        $config = $this->serviceLocator->get('VuFind\Config\PluginManager')->get('AvailabilityFeedback');
        $translator = $this->serviceLocator->get('Laminas\Mvc\I18n\Translator');

        $view = $this->createViewModel();

        $type = $this->params()->fromQuery('type');
        $view->type = $type;

        $pageTitle = '';
        if (isset($config['AF_'.$type]['labelButton'])) {
            $pageTitle = $config['AF_'.$type]['labelButton'];
        }
        $view->pageTitle = $pageTitle;

        $formTitle = '';
        if (isset($config['AF_'.$type]['formTitle'])) {
            $formTitle = $config['AF_'.$type]['formTitle'];
        }
        $view->formTitle = $formTitle;

        $formText = '';
        if (isset($config['AF_'.$type]['formText'])) {
            $formText = $config['AF_'.$type]['formText'];
        }
        $view->formText = $formText;

        $id = $this->params()->fromRoute('id', $this->params()->fromQuery('id'));
        $view->id = $id;
        $recordLoader = $this->serviceLocator->get('VuFind\Record\Loader');;
        $driver = $recordLoader->load($id, 'Solr', false);

        $marcTitle = '';
        if (isset($config['AF_'.$type]['marcTitle'])) {
            $marcTitle = $config['AF_'.$type]['marcTitle'];
        }
        $view->title = $this->getMarcValue($driver, $marcTitle);

        $marcAuthor = '';
        if (isset($config['AF_'.$type]['marcAuthor'])) {
            $marcAuthor = $config['AF_'.$type]['marcAuthor'];
        }
        $view->author = $this->getMarcValue($driver, $marcAuthor);

        $user = $this->getUser();

        $view->userName = $user->username;
        $view->userFullname = $user->firstname.' '.$user->lastname;
        $view->userEmail = $user->email;

        $view->comments = $this->params()->fromPost('comments');

        $view->hideForm = false;
        if ($this->formWasSubmitted('submit', $view->useRecaptcha)) {
            if (empty($view->userEmail)) {
                $this->flashMessenger()->addMessage('bulk_error_missing', 'error');
                return;
            }

            $recipient_email = isset($config['AF_'.$type]['recipientEmail']) ? $config['AF_'.$type]['recipientEmail'] : null;
            if ($recipient_email == null) {
                throw new \Exception(
                    'Result Feedback Module Error: Recipient Email Unset (see AvailabilityFeedback.ini)'
                );
            }

            $recipient_email_subject = isset($config['AF_'.$type]['recipientEmailSubject']) ? $config['AF_'.$type]['recipientEmailSubject'] : null;
            if ($recipient_email_subject == null) {
                throw new \Exception(
                    'Result Feedback Module Error: Recipient Email Subject Unset (see AvailabilityFeedback.ini)'
                );
            }

            $email_message = '';
            $email_message .= $translator->translate('PPN') . ': ' . $view->id . "\n";
            $email_message .= $translator->translate('Title') . ': ' . $view->title . "\n";
            $email_message .= $translator->translate('Author') . ': ' . $view->author . "\n";
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
                    new Address($recipient_email, ''),
                    new Address($view->userEmail, $view->userFullname),
                    $recipient_email_subject,
                    $email_message,
                    null,
                    $view->email
                );

                $formConfirm = '';
                if (isset($config['AF_'.$type]['formConfirm'])) {
                    $formConfirm = $config['AF_'.$type]['formConfirm'];
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
}
