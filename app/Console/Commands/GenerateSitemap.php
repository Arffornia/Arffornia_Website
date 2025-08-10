<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Spatie\Sitemap\Sitemap;
use Spatie\Sitemap\Tags\Url;
use App\Models\News;
use App\Models\ShopItem;

class GenerateSitemap extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sitemap:generate';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate the sitemap for the website';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Generating sitemap...');

        $sitemap = Sitemap::create();

        $sitemap->add(Url::create('/')->setPriority(1.0)->setChangeFrequency(Url::CHANGE_FREQUENCY_WEEKLY));
        $sitemap->add(Url::create('/reglement')->setPriority(0.5)->setChangeFrequency(Url::CHANGE_FREQUENCY_MONTHLY));
        $sitemap->add(Url::create('/stages')->setPriority(0.9)->setChangeFrequency(Url::CHANGE_FREQUENCY_WEEKLY));
        $sitemap->add(Url::create('/shop')->setPriority(0.8)->setChangeFrequency(Url::CHANGE_FREQUENCY_WEEKLY));
        $sitemap->add(Url::create('/news')->setPriority(0.8)->setChangeFrequency(Url::CHANGE_FREQUENCY_DAILY));

        News::all()->each(function (News $news) use ($sitemap) {
            $sitemap->add(
                Url::create("/news/{$news->id}")
                    ->setLastModificationDate($news->updated_at)
                    ->setPriority(0.7)
                    ->setChangeFrequency(Url::CHANGE_FREQUENCY_WEEKLY)
            );
        });

        ShopItem::all()->each(function (ShopItem $item) use ($sitemap) {
            $sitemap->add(
                Url::create("/shop/{$item->id}")
                    ->setLastModificationDate($item->updated_at)
                    ->setPriority(0.7)
                    ->setChangeFrequency(Url::CHANGE_FREQUENCY_WEEKLY)
            );
        });

        $sitemap->writeToFile(public_path('sitemap.xml'));

        $this->info('Sitemap generated successfully!');

        return 0;
    }
}
