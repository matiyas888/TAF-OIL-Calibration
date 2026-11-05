"""
ERPNEXT SERVER SCRIPT: Transhipment Order Petroleum Calculations
================================================================
How to use in ERPNext:
1. Go to: Desk > Customization > Server Script > New
2. Script Type: DocType Event
3. Reference DocType: Transhipment Order (or Stock Entry)
4. DocType Event: before_save (or before_validate)
5. Paste the code below.
================================================================
"""

import math

# ----------------------------------------------------
# 1. ASTM 54B/D CALCULATION HELPER FUNCTIONS
# ----------------------------------------------------
GAS = (346.4228, 0.4388)    # rho15 <= 770
JET = (594.5418, 0.0)       # 770 .. 839
FO  = (186.9696, 0.4862)    # >= 839
TA  = -0.00336312
TB  = 2680.3206
SEED = 778.84

def rnd(x, n=0):
    f = 10.0 ** n
    val = float(f"{x:.15g}") * f
    return (math.floor(val + 0.5) if val >= 0 else math.ceil(val - 0.5)) / f

def trc(x, n=0):
    f = 10.0 ** n
    val = float(f"{x:.15g}") * f
    return (math.floor(val) if val >= 0 else math.ceil(val)) / f

def get_alpha(c, rho):
    if c == 'T':
        return rnd(TA + TB / (rho * rho), 7)
    cols = {'G': GAS, 'J': JET, 'F': FO}
    k0, k1 = cols[c]
    return rnd(k0 / (rho * rho) + k1 / rho, 7)

def vcf_std(a, d):
    return rnd(math.exp(-a * d - rnd(0.8 * (a * d) ** 2, 9)), 6)

def prepare_density(rho, t, t_ref):
    r = rnd(rho / 5.0, 1) * 5.0
    t_round = rnd(t * 2.0, 1) / 2.0
    d = t_round - t_ref
    hy = 1.0 - rnd(23e-6 * d, 9) - rnd(2e-8 * d * d, 9)
    r_t = trc(rnd(r * hy, 2), 2)
    return r_t, t_round

def iterate_pass(c, r_t, d15):
    r = {0: 0.0}
    rr = {0: 0.0}
    a = 0.0
    v = 0.0
    for k in range(1, 8):
        if k == 1:
            a = get_alpha(c, SEED if c == 'T' else r_t)
        elif k == 2:
            a = get_alpha(c, rr[1])
        else:
            prev = r[k - 2] if c == 'T' else rr[k - 2]
            if abs(prev - rr[k - 1]) > 0.05:
                a = get_alpha(c, rr[k - 1])
        v = vcf_std(a, d15)
        r[k] = trc(r_t / v, 3)
        rr[k] = rnd(r[k], 2)
    return {'a': a, 'vcf': v, 'r': r[7], 'rr': rr[7]}

def calculate_density20(rho, t):
    """Returns Standard Density at 20°C (kg/m3)."""
    r_t, t_round = prepare_density(rho, t, 20.0)
    d20 = t_round - 20.0
    d15 = t_round - 15.0
    out = {}
    for c in ['G', 'T', 'J', 'F']:
        it = iterate_pass(c, r_t, d15)
        a = get_alpha(c, it['rr'])
        v = rnd(math.exp(-a * d20 - 8.0 * a * a * d20 - 0.8 * a * a * d20 * d20), 6)
        out[c] = {'rr': it['rr'], 'r20': trc(r_t / v, 3)}

    if 770.5 < out['T']['rr'] < 787.5:
        pick = 'T'
    elif out['G']['rr'] <= 770.0:
        pick = 'G'
    else:
        pick = 'J' if out['F']['rr'] < 839.0 else 'F'
    return rnd(out[pick]['r20'], 1)

def calculate_vcf20(rho20, t):
    """Returns Volume Correction Factor (VCF) to 20°C."""
    t_round = rnd(t * 2.0, 1) / 2.0
    r0 = rnd(rho20 / 5.0, 1) * 5.0
    d = 5.0

    def a_v(k_vals, rho):
        return rnd(trc(trc(k_vals[0] / rho, 8) / rho, 10) + trc(k_vals[1] / rho, 10), 7)

    def a_g(rho):
        return rnd(TA + rnd(trc(TB / rho, 6) / rho, 8), 7)

    def vc(a):
        return rnd(math.exp(-a * d - rnd(a * d * 0.8 * a * d, 9)), 6)

    ks = {'C': GAS, 'H': JET, 'I': FO}
    A = {c: {0: 0.0} for c in ['C', 'G', 'H', 'I']}
    RR = {c: {0: 0.0} for c in ['C', 'G', 'H', 'I']}

    for k in range(1, 9):
        for c in ['C', 'G', 'H', 'I']:
            def alpha_fn(rho):
                return a_g(rho) if c == 'G' else a_v(ks[c], rho)

            if k == 1:
                a = a_g(SEED) if c == 'G' else a_v(ks[c], r0)
            elif k == 2:
                a = alpha_fn(RR[c][1])
            else:
                ref = 'C' if c == 'G' else c
                if abs(RR[ref][k - 2] - RR[ref][k - 1]) > 0.05:
                    a = alpha_fn(RR[c][k - 1])
                else:
                    a = A[ref][k - 1]

            A[c][k] = a
            RR[c][k] = rnd(trc(r0 / vc(a), 3), 2)

    if 770.5 < RR['G'][8] < 787.5:
        r = rnd(RR['G'][8], 1)
    elif RR['C'][8] <= 770.0:
        r = rnd(RR['C'][8], 1)
    else:
        r = rnd(RR['H'][8] if RR['I'][8] < 839.0 else RR['I'][8], 1)

    k_class = GAS if r <= 770.0 else (FO if r > 839.0 else JET)
    a_c = a_v(k_class, r)
    a_t = rnd(TA + rnd(trc(TB / r, 6) / r, 8), 7)

    d20 = t_round - 20.0

    def comp(a):
        t1 = trc(a * d20, 9)
        t2 = rnd(8.0 * trc(a * t1, 9), 9)
        t3 = rnd(t1 * trc(0.8 * trc(a * d20, 10), 9), 9)
        v = rnd(math.exp(-t1 - t2 - t3), 6)
        return rnd(v, 4) if v >= 1.0 else rnd(v, 5)

    return rnd(comp(a_t), 4) if (770.0 < rho20 < 788.0) else rnd(comp(a_c), 4)


# ----------------------------------------------------
# 2. DOCTYPE BEFORE_SAVE / VALIDATE EVENT EXECUTION
# ----------------------------------------------------
# doc is the current Transhipment Order document in Frappe/ERPNext

# A. Sum Compartment Observed Volume (if child table exists)
if hasattr(doc, 'compartments') and doc.compartments:
    total_dip_vol = sum([float(c.volume_litres or 0.0) for c in doc.compartments])
    if total_dip_vol > 0:
        doc.start_observed_litres = total_dip_vol

# B. Start Density & Factor at 20°C
start_rho = float(doc.start_density or 0.0)
start_temp = float(doc.start_temperature or 0.0)

if start_rho > 0 and start_temp != 0:
    doc.start_density_20 = calculate_density20(start_rho, start_temp)
    doc.start_factor = calculate_vcf20(doc.start_density_20, start_temp)
    doc.start_vol20 = round(float(doc.start_observed_litres or 0.0) * doc.start_factor, 2)

# C. Destination / Discharge Metered Volume
m1_to = float(doc.meter_reading_to_1 or 0.0)
m1_from = float(doc.meter_reading_from_1 or 0.0)
m1_drain = float(doc.drained_1 or 0.0)

m2_to = float(doc.meter_reading_to_2 or 0.0)
m2_from = float(doc.meter_reading_from_2 or 0.0)
m2_drain = float(doc.drained_2 or 0.0)

metered_total = (m1_to - m1_from + m1_drain) + (m2_to - m2_from + m2_drain)
doc.metered_total = metered_total

# Final Density & Factor at 20°C
final_rho = float(doc.final_observed_density or doc.start_density or 0.0)
final_temp = float(doc.final_temperature or doc.start_temperature or 0.0)

if final_rho > 0:
    doc.final_density_20 = calculate_density20(final_rho, final_temp)
    doc.final_factor = calculate_vcf20(doc.final_density_20, final_temp)

# Final Volume at 20°C
use_meter = bool(doc.use_metered if hasattr(doc, 'use_metered') else (metered_total > 0))
final_litres = metered_total if use_meter else float(doc.final_observed_litres or 0.0)
doc.final_vol20 = round(final_litres * float(doc.final_factor or 1.0), 2)

# D. Origin Volume at 20°C (Manual Djibouti Waybill Override or Calculated)
origin_vol20 = float(doc.origin_vol20 or 0.0)
if origin_vol20 <= 0:
    origin_vol20 = float(doc.start_vol20 or 0.0)
    doc.origin_vol20 = origin_vol20

# E. Physical Shortage / Loss
loss = abs(origin_vol20 - float(doc.final_vol20 or 0.0))
doc.loss_volume = round(loss, 2)

# F. Contract Allowable Loss
pct = float(doc.allowable_loss_percent or 0.12)
allowed_round = int(round((origin_vol20 * pct) / 100.0))
allowed_2dp = round((origin_vol20 * pct) / 100.0, 2)
doc.allowed_loss_volume = allowed_round
doc.allowed_loss_2dp = allowed_2dp

# G. Two-Branch Debit Note Rule
# Threshold: 150 Litres | Buffer: 0.5 Litres
price = float(doc.price_per_litre or 0.0)

if loss <= 0.0:
    doc.debit_volume = 0.0
    doc.debit_amount = 0.0
    doc.debit_branch = "No Shortage"
elif loss > 150.0:
    # Over 150 L: entire loss debited without allowance
    doc.debit_volume = round(loss, 2)
    doc.debit_amount = round(doc.debit_volume * price, 2)
    doc.debit_branch = "Branch 2 (> 150 L): Full loss debited (0 allowance)"
else:
    # Loss <= 150 L: debit only if excess over 2-decimal allowed is >= 0.5 L
    if (loss - allowed_2dp) >= 0.5:
        doc.debit_volume = max(0.0, round(loss - float(allowed_round), 2))
        doc.debit_amount = round(doc.debit_volume * price, 2)
        doc.debit_branch = "Branch 1 (<= 150 L): Excess >= 0.5 L debited"
    else:
        doc.debit_volume = 0.0
        doc.debit_amount = 0.0
        doc.debit_branch = "Branch 1: Grace buffer waived (Excess < 0.5 L)"

# H. SG @ 15 DJ Difference
sg1 = float(doc.sg_15_dj_1 or 0.0)
sg2 = float(doc.sg_15_dj_2 or 0.0)
if sg1 > 0 and sg2 > 0:
    doc.sg_15_diff = round(sg1 - sg2, 4)
