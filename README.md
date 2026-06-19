> **Mise à jour vers 3.0.0** : cette version supprime la dépendance à `sylius/resource-bundle`
> et contient des changements cassants. Voir le guide de migration : [UPGRADE-3.0.md](UPGRADE-3.0.md).

Installation
============

### Step 1: Download the Bundle

```js
//composer.json
{
    //...
    "require": {
        //...
        "azuracom/process-bundle": "^3.0"
    },
    "repositories":[
        {
          "type": "vcs",
          "url": "git@github.com:azuracom/AzuracomProcessBundle.git"
        }
    ],
}
```

```console
$ composer update
```

If first time downloading a private repo for azuracom, you should have something like this in console:

```console
Could not fetch https://api.github.com/repos/azuracom/AzuracomProcessBundle, please review your configured GitHub OAuth token or enter a new one to access private repos
Head to https://github.com/settings/tokens/new?scopes=repo&description=SomeDescription
to retrieve a token. It will be stored in "/home/thibaut/.config/composer/auth.json" for future use by Composer.
Token (hidden): 
```
Open github link, go to the bottom of the page and click on "Generate token", copy new generated token and past to the console

### Step 2: Enable and configure the Bundle

Then, enable the bundle by adding it to the list of registered bundles
in the `config/bundles.php` file of your project:

```php
// config/bundles.php

return [
    // ...
    Azuracom\ProcessBundle\AzuracomProcessBundle::class => ['all' => true],
];
```

Configure monolog in the `config/packages/monolog.yaml` file of your project:

```yaml
# config/packages/monolog.yaml
monolog:
    channels: ['process']
    handlers:
        process:
            type: service
            id: 'azuracom_process.monolog.process_handler'
            channels: [process]
```

Configure doctrine extensions in the `config/packages/stof_doctrine_extensions.yaml` file of your project:

```yaml
# config/packages/stof_doctrine_extensions.yam
stof_doctrine_extensions:
    orm:
        default:        
            timestampable: true    
```

Configure vich uploader in the `config/packages/vich_uploader.yaml` file of your project:

```yaml
vich_uploader:
  db_driver: orm
  storage: flysystem

  metadata:
    type: attribute
  mappings:
    process:
      uri_prefix: '/process'
      upload_destination: process.storage
      # Will rename uploaded files using a uniqueid as a suffix.
      namer: Vich\UploaderBundle\Naming\SmartUniqueNamer 
```    

Configure flysystem

```yaml
# Read the documentation at https://github.com/thephpleague/flysystem-bundle/blob/master/docs/1-getting-started.md
flysystem:
    storages:
        process.storage:
            adapter: 'local'
            options:
                directory: '%kernel.project_dir%/var/storage/process'        
```   

Configure mapping for user class  in the `config/packages/doctrine.yaml` file of your project:

```yaml
doctrine:
    orm:
        resolve_target_entities:
            Symfony\Component\Security\Core\User\UserInterface: App\Entity\User   
```  


Run migrations
```sh
bin/console make:migration
bin/console doctrine:migration:migrate
```  

If you use docker + localstorage create a volume corresponding to the file location in prod


```yaml
#compose.prod.yaml
# Production environment override
services:
  php:
    # ...
    volumes:           
      - process_logs:/app/var/log/process
      - process_files:/app/var/storage/process

  messenger:
    # ...
    volumes:           
      - process_logs:/app/var/log/process
      - process_files:/app/var/storage/process


volumes:
  process_logs:
  process_files:   
```  

Usage
============

1. Create an handler 

Note that the handler has to implements Azuracom\ProcessBundle\Handler\HandlerInterface

```php
//src/Process/ImportProductHandler.php

namespace App\Process;

use Azuracom\ProcessBundle\Handler\AbstractHandler;
use Azuracom\ProcessBundle\Model\ProcessInterface;
use Doctrine\ORM\EntityManagerInterface;
use App\Entity\Product;

class ImportProductHandler extends AbstractHandler
{

    //const types should starts with 'TYPE_' so the getTypes methods works automatically
    //each tpye const value should be unique in all project
    const TYPE_IMPORT_INTERFACE = 'product_import_interface';
    const TYPE_IMPORT_EMAIL = 'product_import_email';
    //best practice: if not const with TYPE_ exists, the type = static::class (e.g. full classname)

    /** @var EntityManagerInterface */
    protected $em;

    public function __construct(EntityManagerInterface $em)
    {
        $this->em = $em;   
    }
    
    public function handle(): void
    {
        //init stuff
        $this->process->startProcess();
        
        //some sample with csv to datavase
        // ref | name | stock
        if (($handle = fopen("product.csv", "r")) !== false) {
            $i = 1;
            while (($row = fgetcsv($handle, 1000, ",")) !== false) {
                $data = [
                    'ref' => $row[0],
                    'name' => $row[1],
                    'stock' => $row[2],
                ];

                if(!$product = $this->em->getRepository(Product::class)->findOneBy(['ref'=>$data['ref']])){
                    $product = new Product();
                    $product->setReference($data['ref']);
                    $this->helper->info(sprintf("Nouveau produit: %s",$data['ref']));
                }else{
                    $this->helper->info(sprintf("Mise à jour produit: %s",$data['ref']));
                }

                $product->setName($data['name']);
                if(!is_int($data['stock'])){
                    $this->helper->error(sprintf("Ligne %s: stock doit être une valeur entière",$i)); // change process status
                }
                $i++;
            }
        }

        //tag the process as ended
        $this->process->endProcess();
    }

    public static function getTypeLabel($type) :string
    {
        switch($type){
            case self::TYPE_IMPORT_INTERFACE:
                return "Import des produits (depuis l'interface)";

            case self::TYPE_IMPORT_EMAIL:
                return "Import des produits (par email)";
        }
        
    }
}

```

2. Use handler provider in controller or command

```php
//src/Controller/ProductController.php

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Azuracom\ProcessBundle\Handler\HandlerProviderInterface;
use Sylius\Component\Resource\Factory\FactoryInterface;
use App\Process\ImportProductHandler;


#[Route('/product')]
class ProductController extends AbstractController
{

    #[Route("/import",name="product_import")]
    public function import(
        HandlerProviderInterface $provider,
        FactoryInterface $processFactory
    )
    {
        $em = $this->getDoctrine()->getManager();
        $process = $processFactory->createNew();
        $process->setType(ImportProductHandler::TYPE_IMPORT_INTERFACE);
        //both types trigger the same handler
        //$process->setType(ImportProductHandler::TYPE_IMPORT_EMAIL);
        
        $handler = $provider->getHandler($process);
        $handler->handle();
        
        $em->persist($process);
        $em->flush();
    }
}
```

3. Sonata admin 

```yaml
# config/packages/sonata_admin.yaml
sonata_admin:
    groups:
        Process:
            icon: '<i class="fa fa-cog" aria-hidden="true"></i>'
            items:
                - azuracom_process.admin.process
```

```yaml
# config/services.yaml
services:
    Azuracom\ProcessBundle\Controller\ProcessAdminController:
        autoconfigure: true
```

Advanced usage
============

## Configuration


```yaml
# config/packages/azuracom_process.yaml
azuracom_process:
    user_class: App\Entity\User #is set use processFactory to automatically set logged user to process, default value is null
    #overwrite resource, check at SyliusResourceBundle for mor information
    resources:
        process:
            classes:
                model: Azuracom\ProcessBundle\Model\Process
                admin: Azuracom\ProcessBundle\Admin\ProcessAdmin
        process_resource_tag:
            classes:
                model: Azuracom\ProcessBundle\Model\ProcessResourceTag
                admin: Azuracom\ProcessBundle\Admin\ProcessResourceTagAdmin                
```


## Admin configuration

```yaml
# config/packages/azuracom_process.yaml
azuracom_process:
    resources:
        process:
            classes:
                admin: App\Admin\ProcessAdmin
```

```php
<?php

namespace App\Admin;

use App\Process\ImportProductHandler;
use App\Process\ImportWorkingSessionHandler;
use Azuracom\ProcessBundle\Admin\ProcessAdmin as BaseAdmin;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Validator\Constraints\File;

class ProcessAdmin extends  BaseAdmin
{
    public function getAllowedCreationTypes(): array
    {
        //define all the process type that can be create from the admin
        return [
            ImportProductHandler::class,
        ];
    }

    public function getDeferTypes(): array
    {
        return [
            //add an option with a checkbox to le user choose if he want the process to be deferred
            ImportProductHandler::class => 'choice', 
            //when the process is created it has automatically waiting deferred statis 
            //ImportProduct::class => 'force', 
        ];
    }

    public function getFormOptionsKeys($type): array
    {
        //add custom options
        return match ($type) {
            ImportProductHandler::class => [
                ['ignoreInventory', CheckboxType::class, [
                    'label' => "Ignorer les erreurs d'inventaire",
                    'required' => false,
                ]]
            ],
            default => [],
        };
    }


    protected function getFileConstraints($type)
    {
        //define file constrain for each type
        if (in_array($type, [ImportProductHandler::class])) {
            return [
                new File([
                    'maxSize' => '2M',
                    'mimeTypes' => [
                        "text/plain",
                        "application/csv"
                    ],
                ])
            ];
        }

        return parent::getFileConstraints($type);
    }
}
```


## Create a deferred process

### With command + cron

1. Create object and change status

```php
//src/Controller/ProductController.php

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Azuracom\ProcessBundle\Handler\HandlerProviderInterface;
use Azuracom\ProcessBundle\Model\ProcessInterface;
use Sylius\Component\Resource\Factory\FactoryInterface;


#[Route('/product')]
class ProductController extends AbstractController
{
    #[Route("/import",name="product_import")]
    public function import(FactoryInterface $processFactory)
    {
        $em = $this->getDoctrine()->getManager();
        $process = $processFactory->createNew();
        $process->setType(ImportProductHandler::TYPE_IMPORT_INTERFACE);
        $process->setStatus(ProcessInterface::STATUS_WAITING_DEFERRED)
        
        $em->persist($process);
        $em->flush();
    }
}
```


2. Edit cron tab

```console
crontab -e
```

```console
#every day at 8am for example
0 8 * * * php /path/to/project/bin/console azuracom:process:defer-handle
```


```console
sudo service crontab reload
```

### With symfony messenger

1. Create the process and tag with `useMessenger(true)`

```php
//src/Controller/ProductController.php

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Azuracom\ProcessBundle\Handler\HandlerProviderInterface;
use Azuracom\ProcessBundle\Model\ProcessInterface;
use Azuracom\ProcessBundle\Messenger\ProcessMessage;
use Sylius\Component\Resource\Factory\FactoryInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Messenger\MessageBusInterface;


#[Route("/product")]
class ProductController extends AbstractController
{
    #[Route("/import", name: "product_import")]
    public function import(
        FactoryInterface $processFactory,
        MessageBusInterface $bus,
    ): Response {
        $em = $this->getDoctrine()->getManager();
        $process = $processFactory->createNew();
        $process->setType(ImportProductHandler::TYPE_IMPORT_INTERFACE);
        $process->setStatus(ProcessInterface::STATUS_WAITING_DEFERRED);

        //Don't forget this line to avoid command to handle this process
        $process->setUseMessenger(true); 
        
        $em->persist($process);
        $em->flush();

        $bus->dispatch(new ProcessMessage($process->getId()));
    }
}
```

2. Configure the routing

```yaml
# config/packages/messenger.yaml
framework:
    messenger:
        routing:
            Azuracom\ProcessBundle\Messenger\ProcessMessage: async
```

## Autoremove old process

Use symfony scheduler to run a clear command

```php
//src/Schedule.php

<?php

namespace App;

use Symfony\Component\Console\Messenger\RunCommandMessage;
use Symfony\Component\Scheduler\Attribute\AsSchedule;
use Symfony\Component\Scheduler\RecurringMessage;
use Symfony\Component\Scheduler\Schedule as SymfonySchedule;
use Symfony\Component\Scheduler\ScheduleProviderInterface;
use Symfony\Contracts\Cache\CacheInterface;

#[AsSchedule]
class Schedule implements ScheduleProviderInterface
{
    public function __construct(
        private CacheInterface $cache,
    ) {}

    public function getSchedule(): SymfonySchedule
    {
        $schedule = (new SymfonySchedule())
            ->stateful($this->cache) // ensure missed tasks are executed
            ->processOnlyLastMissedRun(true); // ensure only last missed task is run

        // add your own tasks here
        // see https://symfony.com/doc/current/scheduler.html#attaching-recurring-messages-to-a-schedule

        $schedule
            //Clear all succeded process of type xxx older than 2 days
            ->add(RecurringMessage::cron('0 0 * * *', new RunCommandMessage(
                'azuracom:process:clear --type=xxx --status=succeded --modify=\'2 days\''
            )))

        return $schedule;
    }
}

```

## Use datatable for log consultation in admin

Install webpack

https://symfony.com/doc/current/frontend.html

1. Add datatable dependency

```console
yarn add -D simple-datatables
```

2. Crate a stimul controller + ensure stimulus is booted in the admin

```js
// assets/controlers/datatable_controller.js
import { Controller } from '@hotwired/stimulus';
import { DataTable, exportCSV } from "simple-datatables"
import "simple-datatables/dist/style.css";

/* stimulusFetch: 'lazy' */
export default class extends Controller {

    static values = {
        closest: String,
        options: Object
    };

    static targets = ['table', 'exportBtn'];

    connect() {
        const options = this.hasOptionsValue ? this.optionsValue : {};
        const element = this.hasTableTarget ? this.tableTarget : this.element;

        this.dataTable = new DataTable('#' + element.id, {
            ...options,
            perPageSelect: [10, 25, 50, 100, 200]
        });
    }

    exportBtnTargetConnected(btn) {
        btn.style.display = 'block';
        btn.addEventListener('click', this.export);
    }

    exportBtnTargetDisonnected(btn) {
        btn.removeEventListener('click', this.export);
    }

    export = (e) => {
        e.preventDefault();
        exportCSV(this.dataTable, {
            download: true,
            lineDelimiter: "\n",
            columnDelimiter: ";",
            filename: "export.csv"
        })
    }

    disconnect() {
        this.dataTable.destroy();
    }
}

```

### Use easy admin

An abstract controller exists in `Azuracom\ProcessBundle\Controller\BaseProcessCrudController` that sould extends

```php
<?php

namespace App\Controller\Admin;

use App\Process\ImportClientHandler;
use Azuracom\ProcessBundle\Controller\BaseProcessCrudController;

class ProcessCrudController extends BaseProcessCrudController
{

    protected function getCreateChoices(): array
    {
        return [
            [
                'value' => ImportClientHandler::class,
                'label' => ImportClientHandler::getTypeLabel()
            ]
        ];
    }
}


```

And create a link in your dashboard

```php

<?php

namespace App\Controller\Admin;

use EasyCorp\Bundle\EasyAdminBundle\Attribute\AdminDashboard;
use EasyCorp\Bundle\EasyAdminBundle\Config\MenuItem;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractDashboardController;
use Symfony\Component\HttpFoundation\Response;

#[AdminDashboard(routePath: '/admin', routeName: 'admin')]
class DashboardController extends AbstractDashboardController
{
    public function configureMenuItems(): iterable
    {
        yield MenuItem::linkToDashboard('Dashboard', 'mdi:home');
        yield MenuItem::linkTo(ProcessCrudController::class, 'Import/Export', 'mdi:swap-horizontal');
        yield MenuItem::linkToRoute('Retour au site', 'fa:arrow-left', 'app_home');
    }
}
```