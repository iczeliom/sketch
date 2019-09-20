@extends('layouts.default')
@section('title', '出错啦')

@section('content')
<div class="container-fluid">
    <div class="col-sm-10 col-sm-offset-1 col-md-8 col-md-offset-2">
        <div class="panel panel-default">
            <div class="panel-heading">
                <h1>数据冲突</h1>
                <h4>解释：因为一些原因，输入的数据和数据库原有数据发生了冲突，无法完成对应的操作</h4>
            </div>
            <div class="panel-body">
                <h4>可能的原因有：</h4>
                <ul class="font-5">
                    <li>注册时邀请码已用尽，来迟一步</li>
                    <li>相同内容的文章/讨论帖已经建立，不能重复建立雷同数据</li>
                    <li>重复打赏、投票，或余额不足</li>
                    <li>身份错误，试图修改不属于自己的数据</li>
                    <li>网络连接出现问题</li>
                </ul>
                <h4>解决办法：请查看详情参数，核实是否已经建立了文章/讨论帖，检查输入数据，确认无误后重新从正确的入口进入页面提交数据。请勿直接返回/刷新。</h4>
                <h6 class="grayout">详情/参数：{{ $exception->getMessage() }}</h6>
                <h6 class="grayout">（详情代码409，这不是bug）</h6>
            </div>
        </div>
    </div>
</div>
@stop