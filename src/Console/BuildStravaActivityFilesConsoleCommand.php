<?php

namespace App\Console;

use App\Domain\ReadMe;
use App\Domain\Strava\Activity\Activity;
use App\Domain\Strava\Activity\ActivityTotals;
use App\Domain\Strava\Activity\StravaActivityRepository;
use App\Domain\Strava\BikeStatistics;
use App\Domain\Strava\Challenge\StravaChallengeRepository;
use App\Domain\Strava\Gear\StravaGearRepository;
use App\Domain\Strava\MonthlyStatistics;
use App\Infrastructure\Environment\Settings;
use Lcobucci\Clock\Clock;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Twig\Environment;

#[AsCommand(name: 'app:strava:build-files', description: 'Build Strava files')]
class BuildStravaActivityFilesConsoleCommand extends Command
{
    public function __construct(
        private readonly StravaActivityRepository $stravaActivityRepository,
        private readonly StravaChallengeRepository $stravaChallengeRepository,
        private readonly StravaGearRepository $stravaGearRepository,
        private readonly Environment $twig,
        private readonly Clock $clock,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        \Safe\file_put_contents(
            Settings::getAppRoot().'/build/strava-activities-latest.md',
            $this->twig->load('strava-activities.html.twig')->render([
                'activities' => $this->stravaActivityRepository->findAll(5),
                'addLinkToAllActivities' => true,
            ])
        );

        $pathToReadMe = Settings::getAppRoot().'/README.md';
        $readme = ReadMe::fromPathToReadMe($pathToReadMe);

        $allActivities = $this->stravaActivityRepository->findAll();
        $allChallenges = $this->stravaChallengeRepository->findAll();
        $allBikes = $this->stravaGearRepository->findAll();

        $readme
            ->updateStravaTotals($this->twig->load('strava-intro.html.twig')->render([
                'totals' => ActivityTotals::fromActivities(
                    $allActivities,
                    $this->clock->now(),
                ),
            ]))
            ->updateStravaActivities($this->twig->load('strava-activities.html.twig')->render([
                'activities' => $allActivities,
            ]))
            ->updateStravaMonthlyStats($this->twig->load('strava-monthly-stats.html.twig')->render([
                'statistics' => MonthlyStatistics::fromActivitiesAndChallenges(
                    $allActivities,
                    $allChallenges
                ),
            ]))
            ->updateStravaStatsPerBike($this->twig->load('strava-stats-per-bike.html.twig')->render([
                'statistics' => BikeStatistics::fromActivitiesAndGear($allActivities, $allBikes),
            ]))
            ->updateStravaChallenges($this->twig->load('strava-challenges.html.twig')->render([
                'challenges' => $allChallenges,
            ]));

        \Safe\file_put_contents($pathToReadMe, (string) $readme);

        \Safe\file_put_contents(
            Settings::getAppRoot().'/INCOMPLETE-ACTIVITIES.md',
            $this->twig->load('strava-activities.html.twig')->render([
                'activities' => array_filter(
                    $allActivities,
                    fn (Activity $activity) => empty($activity->getCalories()) || empty($activity->getGearId())
                ),
            ])
        );

        return Command::SUCCESS;
    }
}
