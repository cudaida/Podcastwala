<?php namespace App\Console\Commands;
use Illuminate\Console\Command;
use DB;
use App\Podcast;
use App\Item;
use Feeds;

class UpdatePodcastItems extends Command {
    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'updatePodcastItems';
    
    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Update podcast items daily at 5 AM - from RSS feeds';

    /**
     * Update podcast items
     *
     * @return mixed
     */
    public function handle()
    {
        $uniquePodcasts = DB::table('podcasts')
                            ->select('id','feed_url','machine_name')
                            ->groupBy('machine_name')->get();

        foreach($uniquePodcasts as $podcast)
        {
            $usersSubscribedToThisPodcast = DB::table('podcasts')
                                            ->select('user_id','id as podcast_id')
                                            ->where('machine_name','=',$podcast->machine_name)
                                            ->get();

            $items = Feeds::make($podcast->feed_url)->get_items();

            // Calculate 24 hours ago
            $yesterday = time() - (24*1*60*60);

            foreach($items as $item)
            {
                $itemPubDate = $item->get_date();
                if ($item->get_date('U') > $yesterday) {

                    // new items
                    foreach($usersSubscribedToThisPodcast as $subscriber)
                    {
                        Item::create([
                            'user_id' => $subscriber->user_id,
                            'title' => strip_tags($item->get_title()),
                            'description' => strip_tags(str_limit($item->get_description(),100)),
                            'published_at' => $item->get_date('Y-m-d H:i:s'),
                            'url' => $item->get_permalink(),
                            'audio_url' => $item->get_enclosure()->get_link(),
                            'podcast_id' => $subscriber->podcast_id
                        ]);
                    }
                } else {
                    break;
                }
            }

        }

    }
}