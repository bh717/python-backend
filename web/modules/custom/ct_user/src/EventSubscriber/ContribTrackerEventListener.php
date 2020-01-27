<?php

namespace Drupal\ct_user\EventSubscriber;

use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\social_auth\Event\SocialAuthEvents;
use Drupal\social_auth\Event\UserFieldsEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Event subscriptions for events dispatched by Social Auth.
 */
class ContribTrackerEventListener implements EventSubscriberInterface {

  /**
   * The logger Channel Factory.
   *
   * @var \Drupal\Core\Logger\LoggerChannelInterface
   *   The loggerFactory interface alias.
   */
  protected $loggerFactory;

  /**
   * Reacts to the event when users fields are being gathered via Social Auth.
   *
   * @param \Drupal\social_auth\Event\UserFieldsEvent $event
   *   Object returned by UserEvent.
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function onSocialAuthUserFieldEvent(UserFieldsEvent $event) {
    $user = $event->getUserFields();
    $user_email = $user['mail'];

    // Checking if the user has email id axelerant.com and setting status to 1
    // If user has another email domain we do nothing and leave it
    // to the admins.
    if (preg_match('/.*@axelerant\\.com$/i', $user_email) > 0) {

      // Setting the user account status to be active by default.
      $user['status'] = 1;
      $event->setUserFields($user);
      $this->loggerFactory->notice('Auto approved Axelerant user with mail id:  %user_email ', ['%user_email' => $user_email]);
    }

  }

  /**
   * Returns an array of event names this subscriber wants to listen to.
   */
  public static function getSubscribedEvents() {
    return [
      SocialAuthEvents::USER_FIELDS => 'onSocialAuthUserFieldEvent',
    ];
  }

  /**
   * ContribTrackerEventListener constructor.
   *
   * @param \Drupal\Core\Logger\LoggerChannelFactoryInterface $loggerChannelFactory
   *   Logger Channel Factory interface.
   */
  public function __construct(LoggerChannelFactoryInterface $loggerChannelFactory) {
    $this->loggerFactory = $loggerChannelFactory->get('ct_user');
  }

}
