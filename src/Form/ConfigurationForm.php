<?php

/**
 * @file
 * Contains \Drupal\purge_purger_http\Form\ConfigurationForm.
 */

namespace Drupal\purge_purger_http\Form;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\purge\Plugin\Purge\Invalidation\InvalidationsServiceInterface;
use Drupal\purge_ui\Form\PurgerConfigFormBase;
use Drupal\purge_purger_http\Entity\HttpPurgerSettings;

/**
 * Configuration form for the HTTP Purger.
 */
class ConfigurationForm extends PurgerConfigFormBase {

  /**
   * The service that generates invalidation objects on-demand.
   *
   * @var \Drupal\purge\Plugin\Purge\Invalidation\InvalidationsServiceInterface
   */
  protected $purgeInvalidationFactory;

  /**
   * Static listing of all possible requests methods.
   *
   * @todo
   *   Confirm if all relevant HTTP methods are covered.
   *   http://www.w3.org/Protocols/rfc2616/rfc2616-sec9.html
   *
   * @var array
   */
  protected $request_methods = ['BAN', 'GET', 'POST', 'HEAD', 'PUT', 'OPTIONS', 'PURGE', 'DELETE', 'TRACE', 'CONNECT'];

  /**
   * Static listing of the possible connection schemes.
   *
   * @var array
   */
  protected $schemes = ['http', 'https'];

  /**
   * Constructs a \Drupal\purge_purger_http\Form\ConfigurationForm object.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The factory for configuration objects.
   * @param \Drupal\purge\Plugin\Purge\Invalidation\InvalidationsServiceInterface $purge_invalidation_factory
   *   The invalidation objects factory service.
   */
  public function __construct(ConfigFactoryInterface $config_factory, InvalidationsServiceInterface $purge_invalidation_factory) {
    $this->setConfigFactory($config_factory);
    $this->purgeInvalidationFactory = $purge_invalidation_factory;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('purge.invalidation.factory')
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormID() {
    return 'purge_purger_http.configuration_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $settings = HttpPurgerSettings::load($this->getId($form_state));
    $form['tabs'] = [
      '#type' => 'vertical_tabs',
      '#default_tab' => 'edit-connection',
      '#weight' => 10,
    ];

    // Metadata fields.
    $form['name'] = [
      '#title' => $this->t('Name'),
      '#type' => 'textfield',
      '#description' => $this->t('A label that describes this purger.'),
      '#default_value' => $settings->name,
      '#required' => TRUE,
    ];
    $types = [];
    foreach ($this->purgeInvalidationFactory->getPlugins() as $type => $definition) {
      $types[$type] = (string)$definition['label'];
    }
    $form['invalidationtype'] = [
      '#type' => 'select',
      '#title' => $this->t('Type'),
      '#description' => $this->t('What sort of item will this purger clear?'),
      '#default_value' => $settings->invalidationtype,
      '#options' => $types,
      '#required' => FALSE,
    ];

    // The request.
    $form['request'] = [
      '#type' => 'details',
      '#group' => 'tabs',
      '#title' => $this->t('Request'),
      '#description' => $this->t('In this section you configure how a single HTTP request looks like.')
    ];
    $form['request']['hostname'] = [
      '#title' => $this->t('Hostname'),
      '#type' => 'textfield',
      '#default_value' => $settings->hostname,
    ];
    $form['request']['port'] = [
      '#title' => $this->t('Port'),
      '#type' => 'textfield',
      '#default_value' => $settings->port,
    ];
    $form['request']['path'] = [
      '#title' => $this->t('Path'),
      '#type' => 'textfield',
      '#default_value' => $settings->path,
    ];
    $form['request']['request_method'] = [
      '#title' => $this->t('Request Method'),
      '#type' => 'select',
      '#default_value' => array_search($settings->request_method, $this->request_methods),
      '#options' => $this->request_methods,
    ];
    $form['request']['scheme'] = [
      '#title' => $this->t('Scheme'),
      '#type' => 'select',
      '#default_value' => array_search($settings->scheme, $this->schemes),
      '#options' => $this->schemes,
    ];
    $form['request']['verify'] = [
      '#title' => $this->t('Verify SSL certificate'),
      '#type' => 'checkbox',
      '#description' => $this->t("Uncheck to disable certificate verification (this is insecure!)."),
      '#default_value' => $settings->verify,
      '#states' => [
        'visible' => [
          ':input[name="scheme"]' => ['value' => array_search('https', $this->schemes)]
        ]
      ]
    ];

    // Headers.
    if (is_null($form_state->get('headers_items_count'))) {
      $value = empty($settings->headers) ? 1 : count($settings->headers);
      $form_state->set('headers_items_count', $value);
    }
    $form['headers'] = [
      '#type' => 'details',
      '#group' => 'tabs',
      '#title' => $this->t('Headers'),
      '#description' => $this->t('Configure the outbound HTTP headers, leave empty to delete.')
    ];
    $form['headers']['headers'] = [
      '#tree' => TRUE,
      '#type' => 'table',
      '#header' => [$this->t('Header'), $this->t('Value')],
      '#prefix' => '<div id="headers-wrapper">',
      '#suffix' => '</div>'
    ];
    for ($i = 0; $i < $form_state->get('headers_items_count'); $i++) {
      if (!isset($form['headers']['headers'][$i])) {
        $header = isset($settings->headers[$i]) ? $settings->headers[$i] :
          ['field' => '', 'value' => ''];
        $form['headers']['headers'][$i]['field'] = [
          '#type' => 'textfield',
          '#default_value' => $header['field'],
          '#attributes' => ['style' => 'width: 100%;'],
        ];
        $form['headers']['headers'][$i]['value'] = [
          '#type' => 'textfield',
          '#default_value' => $header['value'],
          '#attributes' => ['style' => 'width: 100%;'],
        ];
      }
    }
    $form['headers']['add'] = [
      '#type' => 'submit',
      '#name' => 'add',
      '#value' => t('Add header'),
      '#submit' => [[$this, 'addHeaderSubmit']],
      '#ajax' => [
        'callback' => [$this, 'addHeaderCallback'],
        'wrapper' => 'headers-wrapper',
        'effect' => 'fade',
      ],
    ];

    // Performance.
    $form['performance'] = [
      '#type' => 'details',
      '#group' => 'tabs',
      '#title' => $this->t('Performance'),
    ];
    $form['performance']['timeout'] = [
      '#type' => 'number',
      '#step' => 0.1,
      '#min' => 0.1,
      '#max' => 8.0,
      '#title' => $this->t('Timeout'),
      '#default_value' => $settings->timeout,
      '#required' => TRUE,
      '#description' => $this->t('The timeout of the request in seconds.')
    ];
    $form['performance']['connect_timeout'] = [
      '#type' => 'number',
      '#step' => 0.1,
      '#min' => 0.1,
      '#max' => 4.0,
      '#title' => $this->t('Connection timeout'),
      '#default_value' => $settings->connect_timeout,
      '#required' => TRUE,
      '#description' => $this->t('The number of seconds to wait while trying to connect to a server.')
    ];
    $form['performance']['cooldown_time'] = [
      '#type' => 'number',
      '#step' => 0.1,
      '#min' => 0.0,
      '#max' => 3.0,
      '#title' => $this->t('Cooldown time'),
      '#default_value' => $settings->cooldown_time,
      '#required' => TRUE,
      '#description' => $this->t('Number of seconds to wait after a group of HTTP requests (so that other purgers get fresh content)')
    ];
    $form['performance']['max_requests'] = [
      '#type' => 'number',
      '#step' => 1,
      '#min' => 1,
      '#max' => 500,
      '#title' => $this->t('Maximum requests'),
      '#default_value' => $settings->max_requests,
      '#required' => TRUE,
      '#description' => $this->t("Maximum number of HTTP requests that can be made during Drupal's execution lifetime. Usually PHP resource restraints lower this value dynamically, but can be met at the CLI.")
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * Adds more textfields to the header table.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  public function addHeaderCallback(array &$form, FormStateInterface $form_state) {
    return $form['headers']['headers'];
  }

  /**
   * Let the form rebuild the header table.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  public function addHeaderSubmit(array &$form, FormStateInterface $form_state) {
    $count = $form_state->get('headers_items_count');
    $count++;
    $form_state->set('headers_items_count', $count);
    $form_state->setRebuild();
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {

    // Validate that our timeouts stay between the boundaries purge demands.
    $timeout = $form_state->getValue('connect_timeout') + $form_state->getValue('timeout');
    if ($timeout > 10) {
      $form_state->setErrorByName('connect_timeout');
      $form_state->setErrorByName('timeout', $this->t('The sum of both timeouts cannot be higher than 10.00 as this would affect performance too negatively.'));
    }
    elseif ($timeout < 0.4) {
      $form_state->setErrorByName('connect_timeout');
      $form_state->setErrorByName('timeout', $this->t('The sum of both timeouts cannot be lower as 0.4 as this can lead to too many failures under real usage conditions.'));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitFormSuccess(array &$form, FormStateInterface $form_state) {
    $settings = HttpPurgerSettings::load($this->getId($form_state));
    foreach ($settings as $key => $default_value) {
      if (!is_null($value = $form_state->getValue($key))) {
        if ($key === 'request_method') {
          $settings->$key = $this->request_methods[$value];
        }
        elseif ($key === 'scheme') {
          $settings->$key = $this->schemes[$value];
        }
        elseif ($key === 'headers') {
          $settings->headers = [];
          foreach ($value as $header) {
            if (strlen($header['field'] && strlen($header['value']))) {
              $settings->headers[] = $header;
            }
          }
        }
        else {
          $settings->$key = $value;
        }
      }
    }
    $settings->save();
  }

}
