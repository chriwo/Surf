<?php
namespace TYPO3\Surf\Task\Neos\Flow;

/*                                                                        *
 * This script belongs to the TYPO3 project "TYPO3 Surf"                  *
 *                                                                        *
 *                                                                        */

use TYPO3\Surf\Application\Neos\Flow;
use TYPO3\Surf\Domain\Model\Application;
use TYPO3\Surf\Domain\Model\Deployment;
use TYPO3\Surf\Domain\Model\Node;
use TYPO3\Surf\Domain\Model\Task;
use TYPO3\Surf\Domain\Service\ShellCommandServiceAwareInterface;
use TYPO3\Surf\Domain\Service\ShellCommandServiceAwareTrait;

/**
 * A symlink task for linking a shared Production configuration
 *
 * Note: this might cause problems with concurrent access due to the cached configuration
 * inside this directory.
 *
 *
 * TODO Fix problem with include cached configuration
 */
class SymlinkConfigurationTask extends Task implements ShellCommandServiceAwareInterface
{
    use ShellCommandServiceAwareTrait;

    /**
     * Executes this task
     *
     * @param Node $node
     * @param Application $application
     * @param Deployment $deployment
     * @param array $options
     * @return void
     */
    public function execute(Node $node, Application $application, Deployment $deployment, array $options = array())
    {
        $targetReleasePath = $deployment->getApplicationReleasePath($application);

        if ($application instanceof Flow) {
            $context = $application->getContext();
        } else {
            $context = 'Production';
        }

        $commands = array(
            "cd {$targetReleasePath}/Configuration",
            "if [ -d {$context} ]; then rm -Rf {$context}; fi",
            "mkdir -p ../../../shared/Configuration/{$context}"
        );

        if (strpos($context, '/') !== false) {
            $baseContext = dirname($context);
            $commands[] = "mkdir -p {$baseContext}";
            $commands[] = "ln -snf ../../../../shared/Configuration/{$context} {$context}";
        } else {
            $commands[] = "ln -snf ../../../shared/Configuration/{$context} {$context}";
        }

        $this->shell->executeOrSimulate($commands, $node, $deployment);
    }

    /**
     * Simulate this task
     *
     * @param Node $node
     * @param Application $application
     * @param Deployment $deployment
     * @param array $options
     * @return void
     */
    public function simulate(Node $node, Application $application, Deployment $deployment, array $options = array())
    {
        $this->execute($node, $application, $deployment, $options);
    }
}
