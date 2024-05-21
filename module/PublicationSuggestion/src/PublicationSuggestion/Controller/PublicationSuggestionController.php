<?php
/**
 * Publication Suggestion Controller
 *
 * PHP version 7
 *
 * @category VuFind
 * @package  Controller
 * @author   Oliver Stöhr <stoehr@effective-webwork.de>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org Main Site
 */
namespace PublicationSuggestion\Controller;

use VuFind\Exception\Mail as MailException;
use Laminas\Mail\Address;

/**
 * Publication Suggestion Class
 *
 * Controls the publication suggestion form and mail
 *
 * @category VuFind
 * @package  Controller
 * @author   Oliver Stöhr <stoehr@effective-webwork.de>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org/wiki/development Wiki
 */
class PublicationSuggestionController extends \VuFind\Controller\AbstractBase
{
    /**
     * Display publication suggestion home form.
     *
     * @return \Laminas\View\Model\ViewModel
     */
    public function homeAction()
    {
        return $this->forwardTo('PublicationSuggestion', 'Email');
    }

    /**
     * Receives input from the user and sends an email to the recipient set in
     * the PublicationSuggestion.ini
     *
     * @return void
     */
    public function emailAction()
    {
        $config = $this->serviceLocator->get('VuFind\Config\PluginManager')->get('PublicationSuggestion');

        $account = $this->getAuthManager();
        if ($config['PublicationSuggestion']['force_login'] && $account->isLoggedIn() == false) {
            return $this->forceLogin();
        }

        $translator = $this->serviceLocator->get('Laminas\Mvc\I18n\Translator');
        $view = $this->createViewModel();

        $formText = '';
        if (isset($config['Form']['formText'])) {
            $formText = $config['Form']['formText'];
        }
        $view->formText = $formText;
		$formManual = '';
		if (isset($config['Form']['formManual'])) {
			$formManual = $config['Form']['formManual'];
		}
		$view->formManual = $formManual;

		// Logged in user
        $user = $this->getUser();
        $view->userLoggedInName = trim($user->firstname.' '.$user->lastname);
        $view->userLoggedInId = $user->username;
		$view->userLoggedInMail = $user->email;

		// Recipient
		$this->recipient = [];
		if (isset($config['Recipient']['value'])
			&& isset($config['Recipient']['valueLabel'])
			&& count($config['Recipient']['value']) == count($config['Recipient']['valueLabel'])) {
			$this->recipient['values'] = $config['Recipient']['value']->toArray();
			$this->recipient['labels'] = $config['Recipient']['valueLabel']->toArray();
		}
		$this->recipient['required'] = $config['Recipient']['required'] ?? false;
		$view->recipient = $this->recipient;

		// Author
	    $this->author = [];
		$this->author['placeholder'] = $config['Author']['placeholder'] ?? '';
		$this->author['required'] = $config['Author']['required'] ?? false;
		$view->author = $this->author;

	    // Title
	    $this->title = [];
	    $this->title['placeholder'] = $config['Title']['placeholder'] ?? '';
	    $this->title['required'] = $config['Title']['required'] ?? false;
	    $view->title = $this->title;

	    // Date
	    $this->date = [];
	    $this->date['placeholder'] = $config['Date']['placeholder'] ?? '';
	    $this->date['required'] = $config['Date']['required'] ?? false;
	    $view->date = $this->date;

		// Source
		$this->source = [];
	    if (isset($config['Source']['value'])
		    && isset($config['Source']['valueLabel'])
		    && count($config['Source']['value']) == count($config['Source']['valueLabel'])) {
		    $this->source['values'] = $config['Source']['value']->toArray();
		    $this->source['labels'] = $config['Source']['valueLabel']->toArray();
	    }
	    $this->source['required'] = $config['Source']['required'] ?? false;
		$view->source = $this->source;

	    // Username
	    $this->username = [];
	    $this->username['placeholder'] = $config['Username']['placeholder'] ?? '';
	    $this->username['required'] = $config['Username']['required'] ?? false;
	    $view->username = $this->username;

		// Userid
	    $this->userid = [];
	    $this->userid['placeholder'] = $config['Userid']['placeholder'] ?? '';
	    $this->userid['required'] = $config['Userid']['required'] ?? false;
	    $view->userid = $this->userid;

		// Department
	    $this->department = [];
	    if (isset($config['Department']['value'])
		    && isset($config['Department']['valueLabel'])
		    && count($config['Department']['value']) == count($config['Department']['valueLabel'])) {
		    $this->department['values'] = $config['Department']['value']->toArray();
		    $this->department['labels'] = $config['Department']['valueLabel']->toArray();
	    }
	    $this->department['required'] = $config['Department']['required'] ?? false;
	    $view->department = $this->department;

		// Mail
	    $this->userMail = [];
	    $this->userMail['placeholder'] = $config['Mail']['placeholder'] ?? '';
	    $this->userMail['required'] = $config['Mail']['required'] ?? false;
	    $view->userMail = $this->userMail;

		// Message
	    $this->message = [];
	    $this->message['placeholder'] = $config['Message']['placeholder'] ?? '';
	    $this->message['required'] = $config['Message']['required'] ?? false;
	    $view->message = $this->message;

		// Hold
	    $this->hold = [];
	    $this->hold['value'] = $config['Hold']['value'] ?? '';
	    $this->hold['required'] = $config['Hold']['required'] ?? false;
	    $view->hold = $this->hold;

		// Hint
	    $this->hint = [];
	    $this->hint['placeholder'] = $config['Hint']['placeholder'] ?? '';
	    $this->hint['required'] = $config['Hint']['required'] ?? false;
	    $view->hint = $this->hint;

		// Privacy
	    $this->privacy = [];
	    $this->privacy['value'] = $config['Privacy']['value'] ?? '';
	    $this->privacy['required'] = $config['Privacy']['required'] ?? false;
	    $view->privacy = $this->privacy;

        $view->hideForm = false;

        if ($this->formWasSubmitted('submit', $view->useRecaptcha)) {
			$formUserMail = $this->params()->fromPost('usermail');
            if (empty($formUserMail)) {
                $this->flashMessenger()->addMessage('bulk_error_missing', 'error');
                return;
            }

			$formUserName = $this->params()->fromPost('username') ?? '';
	        $formHold = $this->params()->fromPost('hold') == 'on' ? $view->hold['value'] : '';

            $email_message = '';
            $email_message .= $translator->translate('publication_suggestion_author') . ': ' . $this->params()->fromPost('author') . "\n";
            $email_message .= $translator->translate('publication_suggestion_title') . ': ' . $this->params()->fromPost('title') . "\n";
            $email_message .= $translator->translate('publication_suggestion_date') . ': ' . $this->params()->fromPost('date') . "\n";
            $email_message .= "\n";

            $email_message .= $translator->translate('publication_suggestion_source') . ': ' . $this->params()->fromPost('source') . "\n";
            $email_message .= $translator->translate('publication_suggestion_username') . ': ' . $formUserName . "\n";
            $email_message .= $translator->translate('publication_suggestion_userid') . ': ' . $this->params()->fromPost('userid') . "\n";
            $email_message .= $translator->translate('publication_suggestion_department') . ': ' . $this->params()->fromPost('department') . "\n";
            $email_message .= $translator->translate('publication_suggestion_usermail') . ': ' . $formUserMail . "\n";
            $email_message .= "\n";

            $email_message .= $translator->translate('publication_suggestion_message') . ': ' . $this->params()->fromPost('message') . "\n";
            $email_message .= $translator->translate('publication_suggestion_hold') . ': ' . $translator->translate($formHold) . "\n";
            $email_message .= $translator->translate('publication_suggestion_hint') . ': ' . $this->params()->fromPost('hint') . "\n";

            // This sets up the email to be sent
            // Attempt to send the email and show an appropriate flash message:
            try {
                $mailer = $this->serviceLocator->get('VuFind\Mailer\Mailer');
	            $recipient_email = $this->params()->fromPost('recipient');
	            if (!isset($recipient_email)) {
	                $this->flashMessenger()->addMessage('bulk_error_missing', 'error');
	                return;
	            }
	            $email_sender_address = $config['PublicationSuggestion']['emailSenderAddress'] ?? null;
	            if ($email_sender_address == null) {
	                throw new \Exception(
	                    'Publication Suggestion Module Error: Email Sender Address Unset (see PublicationSuggestion.ini)'
	                );
	            }
	            $email_sender_name = $config['PublicationSuggestion']['emailSenderName'] ?? null;
	            if ($email_sender_name == null) {
	                throw new \Exception(
	                    'Publication Suggestion Module Error: Email Sender Name Unset (see PublicationSuggestion.ini)'
	                );
	            }
	            $email_subject = $config['Form']['emailSubject'] ?? null;
	            if ($email_subject == null) {
	                throw new \Exception(
	                    'Publication Suggestion Module Error: Email Subject Unset (see PublicationSuggestion.ini)'
	                );
	            }
                $mailer->send(
                    new Address($recipient_email, ''),
                    new Address($email_sender_address, $email_sender_name),
                    $email_subject,
                    $email_message,
                    null,
                    new Address($formUserMail, $formUserName)
                );

                $formConfirm = '';
                if (isset($config['Form']['formConfirm'])) {
                    $formConfirm = $config['Form']['formConfirm'];
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
}
