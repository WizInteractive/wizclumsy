@extends('clumsy::templates.head')

<div class="container master">

    @section('breadcrumb')
        @if (isset($breadcrumb) && $breadcrumb)
            {{ HTML::breadcrumb($breadcrumb) }}
        @endif
    @show

    <h1 class="page-header first">
        <div class="row">
            <div class="col-sm-9">
            @section('inner-title')
                {{ $title or '' }}
            @show
            @if (isset($filtersData))
                <i class="glyphicon glyphicon-filter {{ $filtersData['hasFilters'] ? 'active' : '' }}" id="header-filter-btn" data-toggle="collapse" data-target="#filter-colapse"></i>
            @endif
            @if (isset($reorder) && $reorder)
                <a class="glyphicon glyphicon-sort" id="header-reorder-btn" href="{{ route('_active-reorder', $resource) }}"></a>
            @endif
            </div>
            <div class="col-sm-3 after-title">
               @yield('after-title')
            </div>
        </div>
    </h1>

    @yield('before-content')

    <div class="clearfix">
        @yield('before')
        @yield('master')
        @yield('after')
    </div>

    @yield('after-content')

</div>

@extends('clumsy::templates.footer')