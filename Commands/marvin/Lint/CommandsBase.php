<?php

namespace Drush\Commands\marvin\Lint;

use Drush\Commands\marvin\CommandsBase as MarvinCommandsBase;
use League\Container\ContainerInterface;
use Sweetchuck\LintReport\Reporter\BaseReporter;
use Sweetchuck\LintReport\ReporterInterface;
use Sweetchuck\Utils\Filter\ArrayFilterEnabled;

class CommandsBase extends MarvinCommandsBase {

  /**
   * {@inheritdoc}
   */
  protected function getCustomEventNamePrefix(): string {
    return parent::getCustomEventNamePrefix() . ':lint';
  }

  /**
   * {@inheritdoc}
   */
  public function setContainer(ContainerInterface $container) {
    if (!$container->has('lintCheckstyleReporter')) {
      BaseReporter::lintReportConfigureContainer($container);
    }

    return parent::setContainer($container);
  }

  protected function getPresetNameByEnvironmentVariant(): string {
    foreach ($this->getEnvironmentVariants() as $environmentVariant) {
      $presetName = $this->getConfigValue("defaultPreset.$environmentVariant");
      if ($presetName !== NULL) {
        return $presetName;
      }
    }

    return 'default';
  }

  /**
   * @return string[]
   */
  protected function getLintReporterConfigNamesByEnvironmentVariant(): array {
    $reporterCombinations = $this
      ->getConfig()
      ->get('command.marvin.lint.settings.reporterCombination');

    $filter = new ArrayFilterEnabled();
    foreach ($this->getEnvironmentVariants() as $environmentVariant) {
      if (isset($reporterCombinations[$environmentVariant])) {
        return array_filter($reporterCombinations[$environmentVariant], $filter);
      }
    }

    return [];
  }

  protected function getLintReporters(): array {
    $config = $this->getConfig();
    $lintReporterConfigs = $config->get('command.marvin.lint.settings.reporterConfig');
    $lintReporterConfigNames = $this->getLintReporterConfigNamesByEnvironmentVariant();

    $selectedLintReporterConfigs = array_intersect_key(
      $lintReporterConfigs,
      array_flip($lintReporterConfigNames)
      // @todo array_flip(): Can only flip STRING and INTEGER values! CommandsBase.php:67
    );

    return $this->parseLintReporterConfigs($selectedLintReporterConfigs);
  }

  /**
   * @return \Sweetchuck\LintReport\ReporterInterface[]
   */
  protected function parseLintReporterConfigs(array $lintReporterConfigs): array {
    $reporters = [];
    foreach ($lintReporterConfigs as $configId => $config) {
      if (!is_array($config)) {
        $config = ['service' => $config];
      }

      $reporters[$configId] = $this->parseLintReporterConfig($config);
    }

    return $reporters;
  }

  protected function parseLintReporterConfig(array $config): ReporterInterface {
    $config['options']['basePath'] = $this->getProjectRootDir();

    /** @var \Sweetchuck\LintReport\ReporterInterface $reporter */
    $reporter = $this->getContainer()->get($config['service']);
    $reporter->setOptions($config['options']);

    return $reporter;
  }

}
