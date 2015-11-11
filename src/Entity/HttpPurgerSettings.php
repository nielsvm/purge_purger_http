<?php

/**
 * @file
 * Contains \Drupal\purge_purger_http\Entity\HttpPurgerSettings.
 */

namespace Drupal\purge_purger_http\Entity;

use Drupal\purge\Plugin\Purge\Purger\PurgerSettingsBase;
use Drupal\purge\Plugin\Purge\Purger\PurgerSettingsInterface;

/**
 * Defines the HTTP purger settings entity.
 *
 * @ConfigEntityType(
 *   id = "httppurgersettings",
 *   config_prefix = "settings",
 *   static_cache = TRUE,
 *   entity_keys = {"id" = "id"},
 * )
 */
class HttpPurgerSettings extends PurgerSettingsBase implements PurgerSettingsInterface {

  /**
   * The readable name of this purger.
   *
   * @var string
   */
  public $name = '';

  /**
   * The invalidation plugin ID that this purger invalidates.
   *
   * @var string
   */
  public $invalidationtype = 'tag';

  /**
   * The host or IP-address to connect to.
   *
   * @var string
   */
  public $hostname = 'localhost';

  /**
   * The port to connect to.
   *
   * @var int
   */
  public $port = 80;

  /**
   * The HTTP path.
   *
   * @var string
   */
  public $path = '/';

  /**
   * The HTTP request method.
   *
   * @var string
   */
  public $request_method = 'BAN';

  /**
   * The HTTP scheme.
   *
   * @var string
   */
  public $scheme = 'http';

  /**
   * Whether to verify SSL certificates or not.
   *
   * @see http://docs.guzzlephp.org/en/latest/request-options.html#verify
   *
   * @var string
   */
  public $verify = TRUE;

  /**
   * The timeout of the request in seconds.
   *
   * @var float
   */
  public $timeout = 0.5;

  /**
   * The number of seconds to wait while trying to connect to a server.
   *
   * @var float
   */
  public $connect_timeout = 0.2;

  /**
   * Number of seconds to wait after one or more invalidations took place (so
   * that other purgers get fresh content).'
   *
   * @var float
   */
  public $cooldown_time = 0.0;

  /**
   * Maximum number of HTTP requests that can be made during Drupal's execution
   * lifetime. Usually PHP resource restraints lower this value dynamically, but
   * can be met at the CLI.
   *
   * @var int
   */
  public $max_requests = 100;

}
