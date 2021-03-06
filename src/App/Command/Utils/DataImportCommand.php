<?php
declare(strict_types=1);
/**
 * /src/App/Command/Utils/DataImportCommand.php
 *
 * @author  TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
namespace App\Command\Utils;

use App\Services\Rest\Interfaces\Base;
use Box\Spout\Common\Type;
use Box\Spout\Reader\ReaderFactory;
use Box\Spout\Reader\SheetInterface;
use Doctrine\ORM\EntityManager;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Console\Exception\InvalidArgumentException;

/**
 * Class PopulateDateDimensionCommand
 *
 * @package App\Command\Utils
 * @author  TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
class DataImportCommand extends ContainerAwareCommand
{
    /**
     * @var SymfonyStyle
     */
    private $io;

    /**
     * Configures the current command.
     *
     * @throws InvalidArgumentException
     */
    protected function configure()
    {
        // Configure command
        $this
            ->setName('utils:importData')
            ->setDescription('Console command to import data.')
            ->addArgument('filename', InputArgument::REQUIRED, 'Full path to file to import')
            ->addArgument('entity', InputArgument::REQUIRED, 'Entity name where to import data')
            ->addArgument('type', InputArgument::OPTIONAL, 'File type', Type::XLSX)
        ;
    }

    /**
     * Executes the current command.
     *
     * @throws  \LogicException
     * @throws  \Box\Spout\Common\Exception\IOException
     * @throws  \Box\Spout\Common\Exception\UnsupportedTypeException
     * @throws  \Box\Spout\Reader\Exception\ReaderNotOpenedException
     * @throws  \Doctrine\ORM\OptimisticLockException
     * @throws  \Doctrine\ORM\ORMInvalidArgumentException
     * @throws  \Symfony\Component\Console\Exception\InvalidArgumentException
     * @throws  \Symfony\Component\DependencyInjection\Exception\ServiceCircularReferenceException
     * @throws  \Symfony\Component\DependencyInjection\Exception\ServiceNotFoundException
     *
     * @param   InputInterface  $input
     * @param   OutputInterface $output
     *
     * @return  integer
     */
    public function execute(InputInterface $input, OutputInterface $output)
    {
        // Create output decorator helpers for the Symfony Style Guide.
        $this->io = new SymfonyStyle($input, $output);

        $reader = ReaderFactory::create($input->getArgument('type'));
        $reader->open($input->getArgument('filename'));

        /** @var EntityManager $em */
        $em = $this->getContainer()->get('doctrine')->getManager();

        $header = [];
        $count = 0;

        /** @var Base $entityClass */
        $entityClass = '\\App\\Entity\\' . $input->getArgument('entity');

        // Remove current rows
        $em->createQuery('DELETE FROM ' . $entityClass)->execute();

        /** @var SheetInterface $sheet */
        foreach ($reader->getSheetIterator() as $sheet) {
            foreach ($sheet->getRowIterator() as $key => $row) {
                if (empty($header)) {
                    if (trim($row[0]) !== '') {
                        $header = $row;
                    }
                } else {
                    $entity = new $entityClass();

                    foreach (array_combine($header, $row) as $property => $value) {
                        $method = 'set' . ucfirst($property);

                        $value = $this->formatValue($property, (string)$value);

                        if (method_exists($entity, $method)) {
                            $entity->$method($value);
                        }
                    }

                    if ($entityClass === '\\App\\Entity\\Workshop') {
                        $distance = (float)(random_int(1, 10) . '.' . random_int(0, 10));

                        $entity->setDistance($distance);
                    }

                    $em->persist($entity);
                }

                if ($count % 20 === 0) {
                    $em->flush();
                    $em->clear();
                }

                $count++;
            }
        }

        $em->flush();

        $message = sprintf(
            "All done created total of '%d' %s entities.",
            $count,
            $entityClass
        );

        $this->io->success($message);

        return 0;
    }

    /**
     * Simple formatter method for input data.
     *
     * @param   string  $property
     * @param   string  $value
     *
     * @return  string
     */
    private function formatValue(string $property, string $value): string
    {
        switch ($property) {
            case 'postalCode':
                $value = str_pad($value, 5, '0', STR_PAD_LEFT);
                break;
            case 'phone':
                $value = ltrim($value, '0');
                break;
        }

        // Hack to remove all trailing non-ascii whitespaces
        if (preg_match_all('/\p{L}/u', $value, $matches, PREG_OFFSET_CAPTURE) > 0) {
            $lastMatch = $matches[0][count($matches[0]) - 1];

            $value = mb_substr($value, 0, $lastMatch[1] + 1);
        }

        return trim($value);
    }
}
