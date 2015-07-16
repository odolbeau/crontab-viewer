<?php

namespace Bab\CrontabViewer;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Finder\Finder;
use Bab\CrontabViewer\CronJob;
use CalendR\Calendar;

/**
 * GenerateTimelineCommand.
 *
 * @author Olivier Dolbeau <contact@odolbeau.fr>
 */
class GenerateTimelineCommand extends Command
{
    protected $twig;

    public function __construct(\Twig_Environment $twig)
    {
        $this->twig = $twig;

        parent::__construct();
    }

    /**
     * {@inheritDoc}
     */
    protected function configure()
    {
        $this
            ->setName('generate-timeline')
            ->setDescription('Generate an html file to visualize the crontab on a timeline.')
            ->addOption('directory', 'd', InputOption::VALUE_REQUIRED, 'Which folder contains crontabs definitions?', '/var/spool/cron/crontabs/')
            ->addOption('interval', 'i', InputOption::VALUE_REQUIRED, 'Which period should be generated? [d|w|m]', 'd')
            ;
    }

    /**
     * {@inheritDoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $now = new \DateTime();
        $interval = $input->getOption('interval');

        $factory = new Calendar;
        switch ($interval) {
            case 'd':
                $period = $factory->getDay($now);
                break;
            case 'w':
                $period = $factory->getWeek($now);
                break;
            case 'm':
                $period = $factory->getMonth($now);
                break;
            default:
                throw new \InvalidArgumentException("Unknown interval \"$interval\"");
        }

        $finder = new Finder();
        $finder->files()->in($input->getOption('directory'));

        foreach ($finder as $file) {
            $handle = fopen($file->getRealpath(), 'r');
            while (($buffer = fgets($handle, 4096)) !== false) {
                if ('' === trim($buffer) || 0 === strpos($buffer, '#')) {
                    continue;
                }

                $factory->getEventManager()->addProvider(sha1($file->getFilename() . $buffer), CronJobProvider::createFromCronTab($buffer));
            }

            if (!feof($handle)) {
                $output->writeln('<error>Error: unexpected fgets() fail</error>');
            }
            fclose($handle);
        }

        $events = $factory->getEvents($period);

        $crons = [];
        foreach ($events->all() as $event) {
            $crons[] = [
                'id' => $event->getUid(),
                'content' => $event->getCommand(),
                'start' => $event->getBegin()->format('Y-m-d H:i:s'),
                'end' => $event->getEnd()->format('Y-m-d H:i:s')
            ];
        }

        $now = new \DateTime();
        $filename = 'timelines/'.$now->format('Y-m-d-H-i-s').'.html';
        file_put_contents($filename, $this->twig->render('timeline.twig.html', ['crons' => $crons]));

        $output->writeln("<info>Timeline generated: <comment>$filename</comment>.</info>");
    }
}
