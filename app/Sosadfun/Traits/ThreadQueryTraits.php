<?php
namespace App\Sosadfun\Traits;

use DB;
use Cache;
use ConstantObjects;
use Auth;
use App\Models\Thread;
use StringProcess;

trait ThreadQueryTraits{

    public function jinghua_threads()
    {
        return Cache::remember('jinghua-threads', 10, function () {
            $jinghua_tag = ConstantObjects::find_tag_by_name('精华');
            return \App\Models\Thread::with('author','tags')
            ->isPublic()
            ->inPublicChannel()
            ->withTag($jinghua_tag->id)
            ->inRandomOrder()
            ->take(3)
            ->get();
        });
    }

    public function find_top_threads_in_channel($id)
    {
        return Cache::remember('top_threads_in_channel.'.$id, 30, function () use($id) {
            $zhiding_tag = ConstantObjects::find_tag_by_name('置顶');
            return \App\Models\Thread::with('author','tags')
            ->inChannel($id)
            ->withTag($zhiding_tag->id)
            ->get();
        });
    }

    public function process_thread_query_id($request_data)
    {
        $queryid = url('/');
        $selectors = ['inChannel', 'isPublic', 'inPublicChannel', 'withType', 'withBianyuan', 'withTag', 'excludeTag', 'ordered', 'page'];
        foreach($selectors as $selector){
            if(array_key_exists($selector, $request_data)){
                $queryid.='-'.$selector.':'.$request_data[$selector];
            }
        }
        return $queryid;
    }

    public function sanitize_thread_request_data($request)
    {
        $request_data = $request->only('inChannel', 'isPublic', 'inPublicChannel',  'withType', 'withBianyuan', 'withTag', 'excludeTag', 'ordered', 'page');
        if(!Auth::check()||!Auth::user()->isAdmin()){
            $request_data['isPublic']='';
            $request_data['inPublicChannel']='';
        }
        if(!Auth::check()||Auth::user()->level<3){
            $request_data['withBianyuan']='';
        }
        return $request_data;
    }

    public function sanitize_book_request_data($request)
    {
        $request_data = $request->only('inChannel', 'isPublic', 'inPublicChannel',  'withType', 'withBianyuan', 'withTag', 'excludeTag', 'ordered', 'page');
        return $request_data;
    }

    public function find_threads_with_query($query_id, $request_data)
    {
        return Cache::remember('ThreadQ.'.$query_id, 30, function () use($request_data) {
            return Thread::with('author', 'tags', 'last_post')
            ->inChannel(array_key_exists('inChannel',$request_data)? $request_data['inChannel']:'')
            ->isPublic(array_key_exists('isPublic',$request_data)? $request_data['isPublic']:'')
            ->inPublicChannel(array_key_exists('inPublicChannel',$request_data)? $request_data['inPublicChannel']:'')
            ->withType(array_key_exists('withType',$request_data)? $request_data['withType']:'')
            ->withBianyuan(array_key_exists('withBianyuan',$request_data)? $request_data['withBianyuan']:'') //
            ->withTag(array_key_exists('withTag',$request_data)? $request_data['withTag']:'')
            ->excludeTag(array_key_exists('excludeTag',$request_data)? $request_data['excludeTag']:'')
            ->ordered(array_key_exists('ordered',$request_data)? $request_data['ordered']:'latest_add_component')
            ->paginate(config('preference.threads_per_page'))
            ->appends($request_data);
        });
    }

    public function find_books_with_query($query_id, $request_data)
    {
        $time = 60;
        if(!array_key_exists('withTag',$request_data)&&!array_key_exists('excludeTag',$request_data)&&!array_key_exists('ordered',$request_data)&&!array_key_exists('page',$request_data)){$time=5;}
        return Cache::remember('BookQ.'.$query_id, $time, function () use($request_data) {
            $threads = Thread::with('author', 'tags', 'last_component')
            ->isPublic()
            ->withType('book')
            ->inChannel(array_key_exists('inChannel',$request_data)? $request_data['inChannel']:'')
            ->withBianyuan(array_key_exists('withBianyuan',$request_data)? $request_data['withBianyuan']:'') //
            ->withTag(array_key_exists('withTag',$request_data)? $request_data['withTag']:'')
            ->excludeTag(array_key_exists('excludeTag',$request_data)? $request_data['excludeTag']:'')
            ->ordered(array_key_exists('ordered',$request_data)? $request_data['ordered']:'latest_add_component')
            ->paginate(config('preference.threads_per_page'))
            ->appends($request_data);
            $channels = ConstantObjects::find_channels_by_inChannel(array_key_exists('inChannel',$request_data)? $request_data['inChannel']:'');
            $selected_tags = ConstantObjects::find_tags_by_withTag(array_key_exists('withTag',$request_data)? $request_data['withTag']:'');
            $excluded_tags = ConstantObjects::find_tags_by_excludeTag(array_key_exists('excludeTag',$request_data)? $request_data['excludeTag']:'');
            return[
                'threads' => $threads,
                'selected_tags' => $selected_tags,
                'excluded_tags' => $excluded_tags,
                'channels' => $channels,
            ];
        });
    }

    public function convert_book_request_data($request)
    {
        $request_data = $request->only('withBianyuan', 'ordered');
        $withTag='';
        $inChannel='';
        $excludeTag='';

        if($request->channel_id){
            $inChannel=StringProcess::concatenate_channels($request->channel_id);
        }

        if($request->book_length_tag){
            $withTag=StringProcess::concatenate_andTag($request->book_length_tag, $withTag);
        }
        if($request->book_status_tag){
            $withTag=StringProcess::concatenate_andTag($request->book_status_tag, $withTag);
        }
        if($request->sexual_orientation_tag){
            $withTag=StringProcess::concatenate_andTag($request->sexual_orientation_tag, $withTag);
        }
        if($request->withTag){
            $withTag=StringProcess::concatenate_andTag($request->withTag, $withTag);
        }

        if($request->excludeTag){
            $excludeTag=StringProcess::concatenate_excludeTag($request->excludeTag, $excludeTag);
        }

        if($inChannel){
            $request_data = array_merge(['inChannel'=>$inChannel],$request_data);
        }
        if($withTag){
            $request_data = array_merge(['withTag'=>$withTag],$request_data);
        }
        if($excludeTag){
            $request_data = array_merge(['excludeTag'=>$excludeTag],$request_data);
        }

        return $request_data;
    }

    public function sanitize_thread_post_request_data($request)
    {
        $request_data = $request->only('withType', 'withComponent', 'withFolded', 'userOnly', 'withReplyTo', 'inComponent', 'ordered', 'page');
        return $request_data;
    }

    public function process_thread_post_query_id($request_data)
    {
        $queryid = url('/');
        $selectors = ['withType', 'withComponent', 'withFolded', 'userOnly', 'withReplyTo', 'inComponent', 'ordered', 'page'];
        foreach($selectors as $selector){
            if(array_key_exists($selector, $request_data)){
                $queryid.='-'.$selector.':'.$request_data[$selector];
            }
        }
        return $queryid;
    }

    public function find_thread_posts_with_query($thread, $query_id, $request_data)
    {
        $time = 30;
        if(!array_key_exists('withType',$request_data)&&!array_key_exists('withComponent',$request_data)&&!array_key_exists('withFolded',$request_data)&&!array_key_exists('userOnly',$request_data)&&!array_key_exists('withReplyTo',$request_data)&&!array_key_exists('inComponent',$request_data)&&!array_key_exists('ordered',$request_data)){$time=2;}

        return Cache::remember('ThreadPosts.'.$thread->id.$query_id, $time, function () use($thread, $request_data) {
            $posts =  \App\Models\Post::where('thread_id',$thread->id)
            ->with('author.title','last_reply')
            ->withType(array_key_exists('withType',$request_data)? $request_data['withType']:'')//可以筛选显示比如只看post，只看comment，只看。。。
            ->withComponent(array_key_exists('withComponent',$request_data)? $request_data['withComponent']:'')//可以选择是只看component，还是不看component只看post，还是全都看
            ->withFolded(array_key_exists('withFolded',$request_data)? $request_data['withFolded']:'')//是否显示已折叠内容
            ->userOnly(array_key_exists('userOnly',$request_data)? $request_data['userOnly']:'')//可以只看某用户（这样选的时候，默认必须同时属于非匿名）
            ->withReplyTo(array_key_exists('withReplyTo',$request_data)? $request_data['withReplyTo']:'')//可以只看用于回复某个回帖的
            ->inComponent(array_key_exists('inComponent',$request_data)? $request_data['inComponent']:'')//可以只看从这个贴发散的全部讨论
            ->ordered(array_key_exists('ordered',$request_data)? $request_data['ordered']:'')//排序方式
            ->paginate(config('preference.posts_per_page'))
            ->appends($request_data);
            $channel = $thread->channel();
            if($channel->type==='book'){
                $posts->load('chapter');
            }
            if($channel->type==='list'){
                $posts->load('review.reviewee');
            }
            return $posts;
        });
    }
}