/**
 * ERPNEXT CLIENT SCRIPT: Transhipment Order Real-Time Calculation & UI
 * ====================================================================
 * How to use in ERPNext:
 * 1. Go to: Desk > Customization > Client Script > New
 * 2. DocType: Transhipment Order (or Stock Entry)
 * 3. Enabled: Yes
 * 4. Paste the code below.
 * ====================================================================
 */

frappe.ui.form.on('Transhipment Order', {
    refresh: function(frm) {
        // Add Print Debit Note Action Button
        if (!frm.is_new()) {
            frm.add_custom_button(__('Print Debit Note'), function() {
                frappe.set_route('print', frm.doctype, frm.docname);
            }, __('Actions'));

            if (frm.doc.debit_volume > 0) {
                frm.page.set_indicator(__('Debited: ') + frm.doc.debit_volume + ' L (' + format_currency(frm.doc.debit_amount, 'ETB') + ')', 'red');
            } else {
                frm.page.set_indicator(__('Within Allowance (No Debit)'), 'green');
            }
        }

        // Add Quick Pre-fill Test Buttons for TAF Oil Screenshots
        frm.add_custom_button(__('Photo 1 (FDC-99995)'), function() {
            frm.set_value('reference', 'FDC-99995');
            frm.set_value('plate_no', 'A03843/*29988');
            frm.set_value('product_item_number', 'P07-0001');
            frm.set_value('start_density', 789.0);
            frm.set_value('start_temperature', 19.5);
            frm.set_value('origin_vol20', 48710.0);
            frm.set_value('meter_reading_to_1', 162856152);
            frm.set_value('meter_reading_from_1', 162807512);
            frm.set_value('drained_1', 0);
            frm.set_value('sg_15_dj_1', 0.7916);
            frm.set_value('sg_15_dj_2', 0.7923);
            frm.set_value('allowable_loss_percent', 0.12);
            frm.set_value('price_per_litre', 319.76);
            calculate_all(frm);
        }, __('Load Demo'));

        frm.add_custom_button(__('Photo 2 (A40983)'), function() {
            frm.set_value('plate_no', 'A40983/*41304');
            frm.set_value('product_item_number', 'P07-0001');
            frm.set_value('start_density', 787.0);
            frm.set_value('start_temperature', 20.0);
            frm.set_value('origin_vol20', 49829.0);
            frm.set_value('meter_reading_to_1', 162906459);
            frm.set_value('meter_reading_from_1', 162856672);
            frm.set_value('drained_1', 0);
            frm.set_value('sg_15_dj_1', 0.7916);
            frm.set_value('sg_15_dj_2', 0.7907);
            frm.set_value('allowable_loss_percent', 0.12);
            frm.set_value('price_per_litre', 319.76);
            calculate_all(frm);
        }, __('Load Demo'));

        calculate_all(frm);
    },

    // 1. Truck Plate Selection: Fetch Carrier, Trailer, Driver, and Capacity
    plate_no: function(frm) {
        if (!frm.doc.plate_no) return;

        frappe.db.get_doc('Truck Header', frm.doc.plate_no)
            .then(truck => {
                frm.set_value('trailer_plate_no', truck.trailer_plate_no || '');
                frm.set_value('carrier_name', truck.carrier_name || '');
                frm.set_value('driver_name', truck.driver_name || '');
                frm.set_value('driver_licence_no', truck.driver_licence_no || '');
                frm.set_value('nominal_capacity', truck.total_capacity || 0);

                // Auto-generate TO Number: TO-YYYYMMDD-TRUCK
                const today = frappe.datetime.nowdate().replace(/-/g, '');
                const trkCode = truck.truck_no || truck.plate_no.split('/')[0] || 'TRK';
                frm.set_value('to_number', 'TO-' + today + '-' + trkCode);

                frappe.show_alert({
                    message: __('Loaded vehicle & driver: ') + truck.plate_no + ' (' + (truck.driver_name || 'No driver') + ')',
                    indicator: 'green'
                }, 3);
            })
            .catch(() => {
                // If truck doc doesn't exist, allow manual input
            });
    },

    // 2. Product Selection: Fetch allowable loss % and price per litre
    product_item_number: function(frm) {
        if (!frm.doc.product_item_number) return;

        frappe.db.get_value('Item', frm.doc.product_item_number, ['item_name', 'allowable_loss_percent', 'standard_rate'])
            .then(r => {
                if (r && r.message) {
                    if (r.message.allowable_loss_percent) {
                        frm.set_value('allowable_loss_percent', r.message.allowable_loss_percent);
                    }
                    if (r.message.standard_rate) {
                        frm.set_value('price_per_litre', r.message.standard_rate);
                    }
                    calculate_all(frm);
                }
            });
    },

    // 3. Triggers for live calculation
    start_density: function(frm) { calculate_all(frm); },
    start_temperature: function(frm) { calculate_all(frm); },
    origin_vol20: function(frm) { calculate_all(frm); },
    meter_reading_to_1: function(frm) { calculate_all(frm); },
    meter_reading_from_1: function(frm) { calculate_all(frm); },
    drained_1: function(frm) { calculate_all(frm); },
    meter_reading_to_2: function(frm) { calculate_all(frm); },
    meter_reading_from_2: function(frm) { calculate_all(frm); },
    drained_2: function(frm) { calculate_all(frm); },
    allowable_loss_percent: function(frm) { calculate_all(frm); },
    price_per_litre: function(frm) { calculate_all(frm); },

    // 4. SG @ 15 DJ Difference
    sg_15_dj_1: function(frm) { calculate_sg_diff(frm); },
    sg_15_dj_2: function(frm) { calculate_sg_diff(frm); },
});

// Compartment Child Table: Real-Time Dip Interpolation
frappe.ui.form.on('Truck Compartment Dip', {
    dip_mm: function(frm, cdt, cdn) {
        let row = locals[cdt][cdn];
        if (!row.dip_mm || !frm.doc.plate_no) return;

        // Call server to interpolate dip mm to litres for this compartment
        frappe.call({
            method: 'erpnext_petroleum.api.interpolate_dip',
            args: {
                plate_no: frm.doc.plate_no,
                compartment_no: row.compartment_no,
                dip_mm: row.dip_mm
            },
            callback: function(r) {
                if (r.message && r.message.litres) {
                    frappe.model.set_value(cdt, cdn, 'volume_litres', r.message.litres);
                    sum_compartments(frm);
                }
            }
        });
    },
    volume_litres: function(frm) {
        sum_compartments(frm);
    }
});

function sum_compartments(frm) {
    let total = 0;
    (frm.doc.compartments || []).forEach(row => {
        total += flt(row.volume_litres);
    });
    if (total > 0) {
        frm.set_value('start_observed_litres', total);
        calculate_all(frm);
    }
}

function calculate_sg_diff(frm) {
    const sg1 = flt(frm.doc.sg_15_dj_1);
    const sg2 = flt(frm.doc.sg_15_dj_2);
    if (sg1 > 0 && sg2 > 0) {
        frm.set_value('sg_15_diff', flt((sg1 - sg2).toFixed(4)));
    }
}

/**
 * Client-Side Real-Time Calculation Engine
 */
function calculate_all(frm) {
    // 1. Metered Total: (TO - FROM + DRAINED)
    const m1 = flt(frm.doc.meter_reading_to_1) - flt(frm.doc.meter_reading_from_1) + flt(frm.doc.drained_1);
    const m2 = flt(frm.doc.meter_reading_to_2) - flt(frm.doc.meter_reading_from_2) + flt(frm.doc.drained_2);
    const metered_total = Math.max(0, m1 + m2);
    frm.set_value('metered_total', metered_total);

    // 2. Client-side ASTM approximation or API call
    const start_rho = flt(frm.doc.start_density);
    const start_temp = flt(frm.doc.start_temperature);

    // Call server calculation endpoint if available
    frappe.call({
        method: 'erpnext_petroleum.api.evaluate_order',
        args: {
            start_observed_litres: flt(frm.doc.start_observed_litres),
            start_density: start_rho,
            start_temperature: start_temp,
            meters: [
                { from_meter: flt(frm.doc.meter_reading_from_1), to_meter: flt(frm.doc.meter_reading_to_1), drained: flt(frm.doc.drained_1) },
                { from_meter: flt(frm.doc.meter_reading_from_2), to_meter: flt(frm.doc.meter_reading_to_2), drained: flt(frm.doc.drained_2) }
            ],
            origin_vol20_override: flt(frm.doc.origin_vol20),
            allowable_loss_percent: flt(frm.doc.allowable_loss_percent) || 0.12,
            price_per_litre: flt(frm.doc.price_per_litre) || 319.76
        },
        callback: function(r) {
            if (r && r.message) {
                const res = r.message;
                frm.set_value('start_density_20', res.start_density_20);
                frm.set_value('start_factor', res.start_factor);
                frm.set_value('final_density_20', res.final_density_20);
                frm.set_value('final_factor', res.final_factor);
                frm.set_value('final_vol20', res.final_vol20);
                frm.set_value('loss_volume', res.loss_volume);
                frm.set_value('allowed_loss_volume', res.allowed_loss_volume);
                frm.set_value('allowed_loss_2dp', res.allowed_loss_2dp);
                frm.set_value('debit_volume', res.debit_volume);
                frm.set_value('debit_amount', res.debit_amount);
                frm.set_value('debit_branch', res.debit_branch);
            }
        }
    });
}
