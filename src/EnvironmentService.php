<?php

/**
 * Contains \Drupal\environment\EnvironmentService.
 */

namespace Drupal\environment;

/**
 * Provides methods related to environemnts.
 */
class EnvironmentService {

  /**
   * Constructs a ConfigLister.
   *
   */
  public function __construct() {

  }

  /**
   * Switches between two environments.
   *
   * @param string $target_env
   *   Name of the environment to change to.
   * @param bool $force
   *   (optional) Whether to trigger a change even if the environment is the
   *   currently set one. Defaults to FALSE.
   *
   * @return bool
   *   Return TRUE if successful.
   */
  public static function environment_switch($target_env, $force = FALSE, $clear_cache = TRUE) {
    $result = FALSE;
    $messages = array();

    $target_state = environment_load($target_env);

    $current_env = environment_current();

    $override = \Drupal::config('environment.settings')->get('environment_override');

    if (!$force && $current_env == $target_env) {
      drupal_set_message(t("The current environment is already set to '!environment'.",
        array('!environment' => $target_env)), 'notice');

      $result = TRUE;
      // This option is only available in drush.
      if (function_exists('drush_print')) {
        drush_print("To force the environment switch to run anyway, use the '--force' flag.");
      }
    }
    if (!$force && !empty($override)) {
      drupal_set_message(t("The current environment is overriden with '!override'.",
        array('!override' => $override)), 'error');
      // This option is only available in drush.
      if (function_exists('drush_print')) {
        drush_print("To force the environment switch to run anyway, use the '--force' flag.");
      }
    }
    elseif ($current_env != $target_env || $force) {
      if (empty($target_state)) {
        drupal_set_message(t('Environment !environment does not exist.',
          array('!environment' => $target_env)), 'warning');
      }
      else {
        environment_set($target_env);
        \Drupal::moduleHandler()->invokeAll('environment_switch', [$target_env, $current_env]);

        if ($clear_cache) {
          drupal_flush_all_caches();
          drupal_set_message('Cleared cache.');
        }
        $result = TRUE;
      }
    }

    return $result;
  }

  /**
   * Gets the current environment.
   *
   * @param string $workflow
   *   (default: default) Specify an environment workflow to check. If NULL, will
   *   return the current environment state for each workflow. Default workflow
   *   will check environment states not assigned an explicit workflow, this
   *   maintains backwards compatibility.
   * @param string $default
   *   Optional; defaults to NULL. Specify the default value if the current
   *   environment cannot be identified.
   * @param bool $load
   *   (default: FALSE) If TRUE, loads the full environment definition.
   *
   * @return object
   *    environment object
   */
  public static function environment_current($load = FALSE) {
    $current = \Drupal::config('environment.settings')->get('environment');

    return $load ? environment_load($current) : $current;

  }

  /**
   * Se the new environment.
   *
   * @param string $new_env
   *   Machine name of the new system environment.
   */
  public static function environment_set($new_env) {
    \Drupal::configFactory()->getEditable('environment.settings')->set('environment', $new_env)->save();

  }

  /**
   * Fetches all available environments.
   *
   * @param string $env
   *   (optional) Name of the environment. If NULL, will return all environments.
   *   If an array, will return all environments specified in the array.
   * @param bool $reset
   *   (default: FALSE) Reset the static cache and collect new data.
   *
   * @return array
   *   Return all environments or the specified environment.
   */
  public static function environment_load($env = NULL, $reset = FALSE) {
    static $environments;

    if (!isset($environments) || $reset) {
      $environments = \Drupal::entityManager()->getStorage('environment')->loadMultiple();
    }

    if (empty($env)) {
      return $environments;
    }
    else {
      return isset($environments[$env]) ? $environments[$env] : FALSE;
    }
  }


  /**
   * Provides environment form options.
   *
   * @param string $workflow
   *   Optional; specify the workflow for specific options. Defaults to states
   *   that are not part of an explicit workflow.
   * @param string $prefix
   *   Optional; prefix the environment label with the specified string. Defaults
   *   to no prefix.
   * @param bool $reset
   *   Optional; reset the static cache.
   *
   * @return array
   *   Array of form options in the style of environment => label
   */
  public static function _environment_options($prefix = '', $reset = FALSE) {
    static $options;

    if (empty($options) || $reset) {
      $environments = environment_load();
      foreach ($environments as $name => $environment) {
        $options[$name] = $prefix . $environment->get('label');
      }
    }

    return $options;
  }

}
