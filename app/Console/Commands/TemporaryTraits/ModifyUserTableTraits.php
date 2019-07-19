<?php
namespace App\Console\Commands\TemporaryTraits;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Carbon\Carbon;

trait ModifyUserTableTraits{

    public function modifyUserTable()//task 01
    {
        $this->removeDuplicateFollower();
        $this->updateUserInfoNIntro();// task 01.1
        $this->recalculateFollowers();
        $this->deleteExtraUserColumns();// task 01.2
        $this->renameExtraUserColumns();// task 01.
    }

    public function removeDuplicateFollower()
    {
        DB::statement('
            DELETE f1 FROM followers f1
            INNER JOIN
            follower f2
            WHERE
            f1.id < f2.id AND f1.user_id = f2.user_id and f1.follower_id = f2.follower_id;
        ');
        echo "removed duplicated followers table\n";
        Schema::table('followers', function($table){
            $table->unique(['user_id','follower_id']);
        });
    }

    public function updateUserInfoNIntro()//task 01.1
    {
        echo "recalculate users data\n";
        DB::table('users')
        ->where('group','>',10)
        ->update(['role'=>'editor']);

        DB::table('users')
        ->where('admin','=',1)
        ->update(['role'=>'admin']);

        DB::table('users')
        ->where('last_quizzed_at', '<>', null)
        ->update([
            'quiz_level'=>1,
        ]);
        DB::table('users')
        ->update([
            'qiandao_at'=>DB::raw('lastrewarded_at'),
        ]);

        DB::table('users')
        ->where('no_posting','>',Carbon::now())
        ->update(['no_posting_or_not'=>true]);

        echo "task 01.1 start modifying users one by one\n";
        \App\Models\User::chunk(1000, function ($users) {
            $insert_info = [];
            $insert_intro = [];
            foreach($users as $user){
                $user_info = [
                    'user_id' => $user->id,
                    // 'introduction' => $user->introduction,
                    'has_intro' =>false,
                    'xianyu' => $user->xianyu,
                    'jifen' => $user->jifen+$user->shengfan,
                    'sangdian' => $user->sangdian,
                    'exp' => $user->experience_points,
                    'upvote_count' => $user->upvoted,
                    'brief_intro' => \App\Helpers\Helper::trimtext($user->introduction, 40),
                    'activation_token' => $user->activation_token,
                    'invitation_token' => $user->invitation_token,
                    'no_posting_until' => $user->no_posting,
                    'no_logging_until' => $user->no_logging,
                    'qiandao_continued' => $user->continued_qiandao,
                    'qiandao_max' =>$user->maximum_qiandao,
                    'qiandao_all' =>$user->maximum_qiandao,
                    'no_stranger_msg' => $user->receive_messages_from_stranger>0?0:1,
                    'no_upvote_reminders' => $user->no_upvote_reminders,
                    'clicks' =>  $user->clicks,
                    'daily_clicks' =>  $user->daily_clicks,
                    'reply_reminders' =>  $user->reply_reminders+$user->post_reminders+$user->postcomment_reminders+$user->system_reminders,
                    'upvote_reminders' => $user->upvote_reminders,
                    'message_reminders' => $user->message_reminders,
                    'email_verified_at' => $user->activation_token==null? Carbon::now()->toDateTimeString():null,
                    'default_collection_updates' => $user->collection_threads_updated + $user->collection_books_updated,
                    'login_ip' => $user->last_login_ip,
                    'login_at' => $user->last_login,
                ];
                if($user->introduction){
                    $user_intro = [
                        'user_id' => $user->id,
                        'body' => $user->introduction
                    ];
                    $user_info['has_intro']=true;
                    array_push($insert_intro, $user_intro);
                }
                array_push($insert_info, $user_info);

            }
            DB::table('user_infos')->insert($insert_info);
            DB::table('user_intros')->insert($insert_intro);
            echo $user->id."|";
        });
    }

    public function recalculateFollowers()
    {
        echo "start calculating followers\n";
        DB::statement('
            update user_infos
            set follower_count =
            (select count(*) from followers
            where followers.user_id = user_infos.user_id)
        ');
        echo "recalculated followers\n";
        DB::statement('
            update user_infos
            set following_count =
            (select count(*) from followers
            where followers.follower_id = user_infos.user_id)
        ');
        echo "recalculated followings\n";
    }


    public function deleteExtraUserColumns()
    {
        echo "task 1.2.1 delete extra users table\n";
        Schema::table('users', function($table){
            $table->index('created_at');
            $table->dropColumn(['activation_token', 'updated_at', 'shengfan', 'xianyu', 'jifen', 'upvoted', 'downvoted', 'lastresponded_at', 'introduction', 'viewed', 'invitation_token', 'last_login_ip', 'last_login', 'admin', 'superadmin', 'group', 'no_posting', 'no_logging', 'lastrewarded_at', 'sangdian', 'guarden_deadline', 'continued_qiandao', 'post_reminders', 'postcomment_reminders', 'reply_reminders', 'replycomment_reminders', 'message_reminders', 'collection_threads_updated', 'collection_books_updated', 'collection_statuses_updated', 'message_limit', 'receive_messages_from_stranger', 'no_registration', 'upvote_reminders', 'no_upvote_reminders', 'total_char', 'experience_points', 'lastsearched_at', 'maximum_qiandao', 'system_reminders', 'collection_lists_updated', 'collection_list_limit', 'clicks', 'daily_clicks', 'daily_posts', 'daily_chapters', 'daily_characters', 'last_quizzed_at', 'quizzed']);
            echo "echo deleted extra users columns.\n";
        });
    }

    public function renameExtraUserColumns()
    {
        echo "task 1.2.2 rename users table\n";
        Schema::table('users', function($table){
                $table->renameColumn('user_level', 'level');
                $table->renameColumn('no_logging_or_not', 'no_logging');
                $table->renameColumn('no_posting_or_not', 'no_posting');
                echo "echo renamed users table columns.\n";
        });
    }

}