<?php

namespace Azuracom\ProcessBundle\Tests\Functional;

use Azuracom\ProcessBundle\AzuracomProcessBundle;
use Doctrine\Bundle\DoctrineBundle\DoctrineBundle;
use Stof\DoctrineExtensionsBundle\StofDoctrineExtensionsBundle;
use Symfony\Bundle\FrameworkBundle\FrameworkBundle;
use Symfony\Bundle\FrameworkBundle\Kernel\MicroKernelTrait;
use Symfony\Bundle\MonologBundle\MonologBundle;
use Symfony\Component\Console\Application;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Component\HttpKernel\Kernel as BaseKernel;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorage;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

/**
 * Minimal Symfony kernel used to boot the bundle for functional/integration tests.
 *
 * @param array{user_class?: ?string} $processConfig
 */
class TestKernel extends BaseKernel
{
    use MicroKernelTrait;

    public function __construct(private readonly array $processConfig = [])
    {
        parent::__construct('test', false);
    }

    public function registerBundles(): iterable
    {
        return [
            new FrameworkBundle(),
            new MonologBundle(),
            new DoctrineBundle(),
            new StofDoctrineExtensionsBundle(),
            new AzuracomProcessBundle(),
        ];
    }

    protected function configureContainer(ContainerConfigurator $container): void
    {
        $container->extension('framework', [
            'secret' => 'test',
            'test' => true,
            'http_method_override' => false,
            'handle_all_throwables' => true,
            'php_errors' => ['log' => true],
        ]);

        $container->extension('monolog', [
            'handlers' => [
                'main' => [
                    'type' => 'stream',
                    'path' => '%kernel.logs_dir%/test.log',
                    'level' => 'debug',
                ],
            ],
        ]);

        $container->extension('doctrine', [
            'dbal' => [
                'driver' => 'pdo_sqlite',
                'url' => 'sqlite:///:memory:',
            ],
            'orm' => [
                'controller_resolver' => ['auto_mapping' => false],
                'mappings' => [
                    'ProcessBundleTestFixtures' => [
                        'type' => 'attribute',
                        'dir' => __DIR__ . '/Fixtures',
                        'prefix' => 'Azuracom\\ProcessBundle\\Tests\\Functional\\Fixtures',
                        'is_bundle' => false,
                    ],
                ],
            ],
        ]);

        $container->extension('stof_doctrine_extensions', [
            'orm' => [
                'default' => ['timestampable' => true],
            ],
        ]);

        $container->extension('azuracom_process', $this->processConfig);

        // The ProcessFactory expects a token storage; provide a real (empty) one so the bundle
        // wires without pulling SecurityBundle.
        $services = $container->services();
        $services->set('security.token_storage', TokenStorage::class)->public();
        $services->alias(TokenStorageInterface::class, 'security.token_storage');
    }

    public function getCacheDir(): string
    {
        return sys_get_temp_dir() . '/azuracom_process_bundle_test/cache/' . spl_object_id($this);
    }

    public function getLogDir(): string
    {
        return sys_get_temp_dir() . '/azuracom_process_bundle_test/log';
    }
}
