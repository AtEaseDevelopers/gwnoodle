



@canany(['deliveryorder'])
<li class="nav-item {{ Request::is('deliveryOrders*') ? 'active' : '' }}">
    <a class="nav-link {{ Request::is('deliveryOrders*') ? 'active' : '' }}" href="{{ url('/archived/deliveryOrders') }}">
        <i class="nav-icon icon-notebook"></i>
        <span>Arc Delivery Orders</span>
    </a>
</li>
@endcanany
