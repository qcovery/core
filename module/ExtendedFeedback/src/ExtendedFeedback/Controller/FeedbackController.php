<?php
/**
 * Feedback Controller
 *
 * PHP version 7
 *
 * @category VuFind
 * @package  Controller
 * @author   Josiah Knoll <jk1135@ship.edu>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org Main Site
 */
namespace ExtendedFeedback\Controller;

use VuFind\Exception\Mail as MailException;
use Zend\Mail\Address;
use VuFind\Controller\FeedbackController as BasicFeedbackController;

/**
 * Feedback Class
 *
 * Controls the Feedback
 *
 * @category VuFind
 * @package  Controller
 * @author   Josiah Knoll <jk1135@ship.edu>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org/wiki/development Wiki
 */
class FeedbackController extends BasicFeedbackController
{
    /**
     * Receives input from the user and sends an email to the recipient set in
     * the config.ini
     *
     * @return void
     */
    public function emailAction()
    {
        $view = $this->createViewModel();
        $view->useRecaptcha = $this->recaptcha()->active('feedback');
        $view->name = $this->params()->fromPost('name');
        $view->email = $this->params()->fromPost('email');
        $view->comments = $this->params()->fromPost('comments');
        $view->category = $this->params()->fromPost('category');
		$config = $this->serviceLocator->get('VuFind\Config\PluginManager')->get('config');

		// use a simple captcha
        $useSimpleCaptcha = $config->Feedback->simple_captcha ?? false;
		if ($useSimpleCaptcha) {
			session_start();
			if (!isset($_SESSION['captchaFirst']) || !isset($_SESSION['captchaSecond'])) {
				$_SESSION['captchaFirst'] = rand(0, 9);
				$_SESSION['captchaSecond'] = rand(0, 9);
			}
			$captchaResult = $this->params()->fromPost('captcha_result');
			$view->captchaFirst = $_SESSION['captchaFirst'];
			$view->captchaSecond = $_SESSION['captchaSecond'];
			$view->useSimpleCaptcha = $useSimpleCaptcha;
		}

        // Process form submission:
        if ($this->formWasSubmitted('submit', $view->useRecaptcha)) {
            if (empty($view->email) || empty($view->comments)) {
                $this->flashMessenger()->addMessage('bulk_error_missing', 'error');
                return $view;
            }

			if ($useSimpleCaptcha) {
				if ($_SESSION['captchaFirst'] + $_SESSION['captchaSecond'] != $captchaResult) {
					$this->flashMessenger()->addMessage('captcha_wrong', 'error');
					return $view;
				}
			}

            // These settings are set in the feedback settion of your config.ini
            $feedback = isset($config->Feedback) ? $config->Feedback : null;
            $recipient_email = isset($feedback->recipient_email)
                ? $feedback->recipient_email : null;
            $recipient_name = isset($feedback->recipient_name)
                ? $feedback->recipient_name : 'Your Library';
            $email_subject = isset($feedback->email_subject)
                ? $feedback->email_subject : 'VuFind Feedback';
            $sender_email = isset($feedback->sender_email)
                ? $feedback->sender_email : 'noreply@vufind.org';
            $sender_name = isset($feedback->sender_name)
                ? $feedback->sender_name : 'VuFind Feedback';
            $reply_to = (isset($feedback->reply_to) && $feedback->reply_to == 'user_email')
                ? $view->email : null;
            if ($recipient_email == null) {
                throw new \Exception(
                    'Feedback Module Error: Recipient Email Unset (see config.ini)'
                );
            }

            $email_message = empty($view->name) ? '' : 'Name: ' . $view->name . "\n";
            $email_message .= 'Email: ' . $view->email . "\n";
            $email_message .= 'Category: ' . $view->category . "\n";
            $email_message .= 'Comments: ' . $view->comments . "\n\n";

            // This sets up the email to be sent
            // Attempt to send the email and show an appropriate flash message:
            try {
                $mailer = $this->serviceLocator->get('VuFind\Mailer\Mailer');
                $mailer->send(
                    new Address($recipient_email, $recipient_name),
                    new Address($sender_email, $sender_name),
                    $email_subject, $email_message,
                    null,
                    $reply_to
                );
                $this->flashMessenger()->addMessage(
                    'Thank you for your feedback.', 'success'
                );
            } catch (MailException $e) {
                $this->flashMessenger()->addMessage($e->getMessage(), 'error');
            }
		    unset($_SESSION['captchaFirst']);
		    unset($_SESSION['captchaSecond']);
        }
        return $view;
    }
}
