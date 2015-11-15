@section('navbar-buttons')
    <ul class="nav navbar-nav navbar-right">
        <li><a href="{{ route('clumsy.logout') }}" class="with-tooltip glyphicon glyphicon-off" title="{{ trans('clumsy::buttons.logout') }}" data-placement="bottom"></a></li>
    </ul>

    <ul class="nav navbar-nav navbar-right">
        <li class="dropdown with-tooltip" title="{{ trans('clumsy::buttons.session') }}" data-placement="bottom">
            <a href="#" class="glyphicon glyphicon-user dropdown-toggle hidden-xs" data-toggle="dropdown"></a>
            <a href="#" class="dropdown-toggle visible-xs" data-toggle="dropdown">
            {{ trans('clumsy::buttons.session') }} <b class="caret"></b>
            </a>
            <ul class="dropdown-menu">
                @if ($user->isGroupable())
                    <li role="presentation" class="dropdown-header">{{ $user->usergroup }}</li>
                @endif
                <li role="presentation">
                    <a href="{{ route("$adminPrefix.user.edit", $user->id) }}">{{ $user->username }}</a>
                </li>
            </ul>
        </li>
    </ul>

    @if (Overseer::canManageUsers())
    <ul class="nav navbar-nav navbar-right">
        <li class="dropdown with-tooltip" title="{{ trans('clumsy::buttons.manage_site') }}" data-placement="bottom">
            <a href="#" class="glyphicon glyphicon-cog dropdown-toggle hidden-xs" data-toggle="dropdown"></a>
            <a href="#" class="dropdown-toggle visible-xs" data-toggle="dropdown">
                {{ trans('clumsy::buttons.manage_site') }} <b class="caret"></b>
            </a>
            <ul class="dropdown-menu">
                <li role="presentation">
                    <a href="{{ route("$adminPrefix.user.index") }}">{{ trans('clumsy::buttons.manage_users') }}</a>
                </li>
            </ul>
        </li>
    </ul>
    @endif
@show