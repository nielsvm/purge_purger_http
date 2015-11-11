<?php

/**
 * @file
 * Contains \Drupal\purge_purger_http\Plugin\Purge\Purger\HttpPurger.
 */

namespace Drupal\purge_purger_http\Plugin\Purge\Purger;

use GuzzleHttp\Client;
use GuzzleHttp\ClientInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\purge\Plugin\Purge\Purger\PurgerBase;
use Drupal\purge\Plugin\Purge\Purger\PurgerInterface;
use Drupal\purge\Plugin\Purge\Invalidation\InvalidationInterface;
use Drupal\purge_purger_http\Entity\HttpPurgerSettings;

/**
 * Generic HTTP Purger
 *
 * Generic and highly configurable purger that makes HTTP requests, best suits
 * custom configurations.
 *
 * @PurgePurger(
 *   id = "http",
 *   label = @Translation("Generic HTTP Purger"),
 *   configform = "\Drupal\purge_purger_http\Form\ConfigurationForm",
 *   cooldown_time = 0.0,
 *   description = @Translation("Generic and highly configurable purger that makes HTTP requests, best suits custom configurations."),
 *   multi_instance = TRUE,
 *   types = {},
 * )
 */
class HttpPurger extends PurgerBase implements PurgerInterface {

  /**
   * @var \GuzzleHttp\Client
   */
  protected $client;

  /**
   * The settings entity holding all configuration.
   *
   * @var \Drupal\purge_purger_http\Entity\HttpPurgerSettings
   */
  protected $settings;

  /**
   * Constructs the HTTP purger.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \GuzzleHttp\ClientInterface $http_client
   *   An HTTP client that can perform remote requests.
   */
  function __construct(array $configuration, $plugin_id, $plugin_definition, ClientInterface $http_client) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->settings = HttpPurgerSettings::load($this->getId());
    $this->client = $http_client;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('http_client')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function delete() {
    HttpPurgerSettings::load($this->getId())->delete();
  }

  /**
   * {@inheritdoc}
   */
  public function invalidate(array $invalidations) {
    // @todo: this obviously needs to be implemented.
    foreach ($invalidations as $invalidation) {
      $invalidation->setState(InvalidationInterface::FAILED);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getCooldownTime() {
    return $this->settings->cooldown_time;
  }

  /**
   * {@inheritdoc}
   */
  public function getIdealConditionsLimit() {
    return $this->settings->max_requests;
  }

  /**
   * {@inheritdoc}
   */
  public function getLabel() {
    if ($this->settings->name) {
      return $this->settings->name;
    }
    else {
      return parent::getLabel();
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getTimeHint() {
    // Theoretically connection timeouts and general timeouts can add up, so
    // we add up our assumption of the worst possible time it takes as well.
    return $this->settings->connect_timeout + $this->settings->timeout;
  }

  /**
   * {@inheritdoc}
   */
  public function getTypes() {
    return [$this->settings->invalidationtype];
  }

}
