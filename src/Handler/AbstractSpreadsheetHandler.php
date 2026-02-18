<?php

namespace Azuracom\ProcessBundle\Handler;

use Azuracom\ProcessBundle\Helper\Counter;
use Azuracom\ProcessBundle\Model\ProcessInterface;
use Azuracom\SpreadsheetToObject\Helper\DataMatcher;
use Azuracom\SpreadsheetToObject\Spreadsheet\HandlerInterface;
use Doctrine\ORM\EntityManagerInterface;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Symfony\Contracts\Translation\TranslatorInterface;
use Vich\UploaderBundle\Storage\FileSystemStorage;

abstract class AbstractSpreadsheetHandler extends AbstractHandler
{
    protected ?HandlerInterface $spreadsheetHandler = null;
    protected ?Counter $counter = null;
    protected ?DataMatcher $dataMatcher = null;

    public function __construct(
        protected ?EntityManagerInterface $em = null,
        protected ?FileSystemStorage $fileSystemStorage = null,
        protected ?TranslatorInterface $translator = null,
    ) {
    }

    public function configure(): void
    {
        parent::configure();
        $this->counter = new Counter();
        $this->dataMatcher = new DataMatcher();
    }

    public function handle(): void
    {
        $this->process->startProcess();
        
        $url = $this->fileSystemStorage->resolvePath($this->process, 'file', null, false);
        $objReader = null;

        //open file
        try {
            $objReader = IOFactory::createReaderForFile($url);
        } catch (\Exception $e) {
            $this->helper->error($this->translator->trans("azuracom_process.errors.file_openning_failed"));
        }

        if ($objReader) {
            //load data from target sheet
            $objReader->setReadDataOnly(true);
            $objPHPExcel = $objReader->load($url);

            if ($worksheet = $objPHPExcel->getSheet(0)) {
                $this->read($worksheet);
            } else {
                $this->helper->error($this->translator->trans("azuracom_process.errors.sheet_not_found"));
            }
        }

        if ($this->process->getStatus() === ProcessInterface::STATUS_HAS_ERROR) {

            foreach ($this->getClearClassNames() as $className) {
                $this->em->clear($className);
            }

            //if user was clear from the entity manager, so reset manually process user with a reference to avoid cascade persit bug
            if ($user = $this->process->getUser()) {
                $this->process->setUser($this->em->getReference(get_class($user), $user->getId()));
            }
        } else {
            $this->helper->info($this->getSuccessMessage());
        }

        $this->process->endProcess();
    }

    abstract protected function read(Worksheet $worksheet): void;
    abstract protected function getClearClassNames(): array;
    abstract protected function getSuccessMessage(): string;
}
