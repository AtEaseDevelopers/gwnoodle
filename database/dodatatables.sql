select
    deliveryorders.id,
    deliveryorders.dono,
    deliveryorders.date,
    deliveryorders.driver_id,
    deliveryorders.lorry_id,
    deliveryorders.vendor_id,
    deliveryorders.source_id,
    deliveryorders.remark,
    deliveryorders.destinate_id,
    deliveryorders.item_id,
    deliveryorders.weight,
    deliveryorders.shipweight,
    deliveryorders.fees,
    deliveryorders.tol,
    deliveryorders.billingrate,
    deliveryorders.commissionrate,
    deliveryorders.status,
    CONCAT(
        deliveryorders.id,
        ":",
        deliveryorders.billingrate
    ) as billingrate_data,
    CONCAT(
        deliveryorders.id,
        ":",
        deliveryorders.commissionrate
    ) as commissionrate_data,
    CONCAT(deliveryorders.id, ":", COUNT(claims.id)) as claims
from
    arc_deliveryorders deliveryorders
left join claims claims on claims.deliveryorder_id = deliveryorders.id
left join drivers driver on driver.id = deliveryorders.driver_id
left join lorrys lorry on lorry.id = deliveryorders.lorry_id
left join items item on item.id = deliveryorders.item_id
left join vendors vendor on vendor.id = deliveryorders.vendor_id
left join locations sources on sources.id = deliveryorders.source_id
left join locations destinate on destinate.id = deliveryorders.destinate_id
group by
    deliveryorders.id,
    deliveryorders.dono,
    deliveryorders.date,
    deliveryorders.driver_id,
    deliveryorders.lorry_id,
    deliveryorders.vendor_id,
    deliveryorders.source_id,
    deliveryorders.remark,
    deliveryorders.destinate_id,
    deliveryorders.item_id,
    deliveryorders.weight,
    deliveryorders.shipweight,
    deliveryorders.fees,
    deliveryorders.tol,
    deliveryorders.billingrate,
    deliveryorders.commissionrate,
    deliveryorders.status
order by deliveryorders.id desc