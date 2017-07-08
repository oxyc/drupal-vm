<?php
/**
 * @file
 * Contains JJG\DrupalVM|Plugin.
 */

namespace JJG\DrupalVM;

use Composer\Composer;
use Composer\EventDispatcher\EventSubscriberInterface;
use Composer\Factory;
use Composer\IO\IOInterface;
use Composer\Plugin\PluginInterface;
use Composer\Script\Event;
use Composer\Script\ScriptEvents;

class Plugin implements PluginInterface, EventSubscriberInterface {

    /**
     * @var \Composer\Composer
     */
    protected $composer;

    /**
     * @var \Composer\IO\IOInterface
     */
    protected $io;

    /**
     * {@inheritdoc}
     */
    public function activate(Composer $composer, IOInterface $io) {
        $this->composer = $composer;
        $this->io = $io;
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents() {
        return array(
            ScriptEvents::POST_INSTALL_CMD => 'addFiles',
            ScriptEvents::POST_UPDATE_CMD => 'addFiles',
        );
    }

    /**
     * Add/update required Drupal VM files.
     *
     * @param \Composer\Script\Event $event
     */
    public function addFiles(Event $event) {
        $this->addVagrantfile();
        $this->addConfigFile();
    }

    /**
     * Add/update project Vagrantfile.
     */
    public function addVagrantfile() {
        $baseDir = dirname(Factory::getComposerFile());
        $source = __DIR__ . '/../../Vagrantfile';
        $target =  $baseDir . '/Vagrantfile';

        if (file_exists($source)) {
            if (!file_exists($target) || md5_file($source) != md5_file($target)) {
                $isLegacy = $this->isLegacyVagrantfile($target);

                copy($source, $target);

                $extra = $this->composer->getPackage()->getExtra();
                if ($isLegacy && !isset($extra['drupalvm']['config_dir'])) {
                    $this->io->writeError(
                        '<warning>'
                        . 'Drupal VM has been updated and consequently written over your Vagrantfile which from now on will be managed by Drupal VM. '
                        . 'Due to this change, you are required to set the `config_dir` location in your composer.json file:'
                        . "\n"
                        . "\n  $ composer config extra.drupalvm.config_dir <sub-directory>"
                        . "\n"
                        . '</warning>'
                    );
                }
            }
        }
    }

    /**
     * Add a project config.yml file.
     *
     * @param \Composer\Script\Event $event
     */
    public function addConfigFile() {
        $baseDir = dirname(Factory::getComposerFile());
        $extra = $this->composer->getPackage()->getExtra();

        $configDir = isset($extra['drupalvm']['config_dir']) ? $extra['drupalvm']['config_dir'] : '';
        $docroot = isset($extra['drupalvm']['docroot']) ? $extra['drupalvm']['docroot'] : 'web';

        $target = implode(DIRECTORY_SEPARATOR, array_filter([$baseDir, $configDir, 'config.yml']));

        if (!file_exists($target)) {
            $config = '---'
                . "\n" . 'vagrant_hostname: drupalvm.dev'
                . "\n" . 'vagrant_machine_name: drupalvm'
                . "\n" . 'vagrant_ip: 192.168.88.88'
                . "\n"
                . "\n" . 'drupal_build_composer_project: false'
                . "\n" . 'drupal_build_composer: true'
                . "\n"
                . "\n" . 'drupal_composer_path: false'
                . "\n" . 'drupal_composer_install_dir: /var/www/drupalvm'
                . "\n" . 'drupal_core_path: "{{ drupal_composer_install_dir }}/' . $docroot . '"'
                . "\n";

            mkdir(dirname($target), 0755, true);
            file_put_contents($target, $config);

            $this->io->write(sprintf(
                '<info>Drupal VM has scaffolded a configuration file: <comment>%s</comment></info>',
                (!empty($configDir) ? $configDir . '/' : '') . 'config.yml'
            ));
        }
    }

    /**
     * Return if the parent project is using the < 5.0.0 delegating Vagrantfile.
     *
     * @return bool
     */
    private function isLegacyVagrantfile($vagrantfile) {
        if (!file_exists($vagrantfile)) {
            return false;
        }
        return strpos(file_get_contents($vagrantfile), '# Load the real Vagrantfile') !== false;
    }
}
