# ERPNext Petroleum & Transhipment Customization Guide
### TAF Oil Operations: ASTM 54B/D Density Correction, Metered Discharge, Compartment Dips & Transporter Debit Engine

---

## 1. Overview & Architecture

This package provides a 100% faithful port of the TAF Oil petroleum calculation engine reverse-engineered from MS Access VBA, `CALCUL_DE_DENSITE_STANDATD.xlsx`, and ERP historical records.

### Key Features:
- **ASTM 54B/D Standard Density & VCF**: 7-pass iteration with hydrometer glass thermal correction across Gasoline, Jet A1, Transition, and Fuel Oil product groups.
- **Dual Meter Discharge**: Summation of Meter 1 and Meter 2 $(TOMET - FROMMET + DRAINED)$.
- **Truck Dip Interpolation**: Calibration table lookup with linear interpolation yielding whole litres.
- **Two-Branch Carrier Debit Rule**:
  - $\le 150 \text{ L}$ loss: Deduct contractual allowance ($0.12\%$ or $0.08\%$) if net loss exceeds allowance by at least $0.5 \text{ L}$.
  - $> 150 \text{ L}$ loss: **Zero allowance granted**; full physical shortage is debited at the contract price per litre.
- **Carrier & Fleet Master**: Selecting a Truck Plate auto-fills Trailer Plate, Carrier/Transporter, Driver Name, Driver Licence, and Truck Capacity.
- **Debit Note Voucher**: Ready-to-print Jinja voucher with GL posting accounting codes (Dr `122010-100` / Cr `161022`).

---

## 2. File Manifest

| File | Purpose | ERPNext Location |
| :--- | :--- | :--- |
| `petroleum_calculator.py` | Complete standalone calculation library & unit test suite | Python module / backend app |
| `server_script.py` | Automated server-side validation & calculations on save | Desk > Customization > Server Script |
| `client_script.js` | Real-time interactive UI, truck auto-fill, demo buttons | Desk > Customization > Client Script |
| `print_format_debit_note.html`| Debit note print format replacing Access `RPTtempdebitnote` | Desk > Customization > Print Format |
| `doctype_definitions.json` | JSON schema for all 4 custom DocTypes | Desk > Customization > DocType |

---

## 3. Installation Guide (Low-Code / Desk Customization)

### Step 1: Create Custom DocTypes
You can create these DocTypes via **Desk > Customization > DocType > New**:

1. **`Truck Calibration`** (Child DocType):
   - Set **Is Child Table**: Checked
   - Fields:
     - `compartment_no` (Int, Mandatory)
     - `dip_mm` (Float, Mandatory)
     - `litres` (Float, Mandatory)

2. **`Truck Compartment Dip`** (Child DocType):
   - Set **Is Child Table**: Checked
   - Fields:
     - `compartment_no` (Int, Mandatory)
     - `dip_mm` (Float, Mandatory)
     - `volume_litres` (Float, Read Only)

3. **`Truck Header`** (Standard Master DocType):
   - Set **Module**: `Stock`
   - Set **Auto Name**: `field:plate_no`
   - Fields:
     - `plate_no` (Data, Unique, Mandatory)
     - `truck_no` (Data)
     - `trailer_plate_no` (Data)
     - `carrier_name` (Data)
     - `driver_name` (Data)
     - `driver_licence_no` (Data)
     - `total_capacity` (Float)
     - `number_of_compartments` (Int, Default: 4)
     - `calibration_table` (Table -> `Truck Calibration`)

4. **`Transhipment Order`** (Standard Transaction DocType):
   - Set **Module**: `Stock`
   - Set **Is Submittable**: Checked
   - Set **Auto Name**: `format:TO-{YYYY}{MM}{DD}-{plate_no}`
   - Add fields as defined in `doctype_definitions.json`.

*(Alternatively, if using an existing DocType like `Stock Entry` or `Purchase Receipt`, you can add these fields as custom fields via **Desk > Customization > Custom Field**).*

---

### Step 2: Install Server Script
1. Navigate to: **Desk > Customization > Server Script > New**
2. Configure:
   - **Script Type**: `DocType Event`
   - **Reference DocType**: `Transhipment Order`
   - **DocType Event**: `before_save` (or `before_validate`)
   - **Enabled**: Checked
3. Open `server_script.py`, copy all contents, and paste into the script editor.
4. Click **Save**.

---

### Step 3: Install Client Script
1. Navigate to: **Desk > Customization > Client Script > New**
2. Configure:
   - **DocType**: `Transhipment Order`
   - **Enabled**: Checked
3. Open `client_script.js`, copy all contents, and paste into the script editor.
4. Click **Save**.

---

### Step 4: Install Print Format
1. Navigate to: **Desk > Customization > Print Format > New**
2. Configure:
   - **DocType**: `Transhipment Order`
   - **Name**: `Transporter Debit Note`
   - **Standard**: No
   - **Custom Format**: Checked
   - **Print Format Type**: `Jinja`
3. Open `print_format_debit_note.html`, copy all contents, and paste into the HTML editor.
4. Click **Save**.

---

## 4. How to Add & Manage Truck Calibrations in ERPNext

There are two easy methods to add truck dip calibrations into ERPNext:

### Method A: Directly from the Desk Form (Single Truck)
1. Go to: **Desk > Stock > Truck Header > [Select or New Truck]**
2. In the **Vehicle Details** section:
   - Enter **Truck Plate No** (e.g., `A03843/*29988`)
   - Enter Driver Name, Licence No, Trailer Plate, and Carrier
3. Scroll down to the **Dip Calibration Chart** child table:
   - Click **Add Row**
   - Enter:
     - `Compartment #` (e.g. `1`)
     - `Dip (mm)` (e.g. `100.0`)
     - `Calibrated Litres` (e.g. `750.0`)
   - Repeat for all dip calibration points from your calibration certificate.
4. Click **Save**.

### Method B: Bulk Upload via ERPNext Data Import Tool
1. Go to: **Desk > Settings > Data Import > New**
2. Select **DocType**: `Truck Header`
3. Click **Download Template**:
   - Check **Include Child Records: Truck Calibration**
   - Select CSV format
4. Fill in the columns:
   ```csv
   name,carrier_name,total_capacity,calibration_table.compartment_no,calibration_table.dip_mm,calibration_table.litres
   A03843/*29988,TAF Transport,48000,1,100,750
   A03843/*29988,TAF Transport,48000,1,200,1500
   A03843/*29988,TAF Transport,48000,1,300,2250
   A03843/*29988,TAF Transport,48000,1,1000,7500
   ```
5. Upload the CSV and click **Start Import**. All calibration points will be created in seconds.

---

## 5. Verification & Demo Testing

Inside any Transhipment Order document in ERPNext:
1. Click the **Load Demo** menu at the top right of the form.
2. Select **Photo 1 (FDC-99995)**:
   - Plate: `A03843/*29988`
   - Loading Vol @ 20°C: `48,710 L`
   - Meter 1 Readings: `162856152` to `162807512` = `48,640 L`
   - Final Density @ 20°C: `787.5 kg/m3`, Final VCF: `1.000000`
   - Physical Shortage: `70 L` ($0.144\%$)
   - Allowed Loss ($0.12\%$): `58 L`
   - Result: Shortage is under $150 \text{ L}$ and difference ($12 \text{ L}$) is within normal tolerance $\rightarrow$ **Debit: 0 L (0.00 ETB)**.
3. Select **Photo 2 (A40983)**:
   - Plate: `A40983/*41304`
   - Loading Vol @ 20°C: `49,829 L`
   - Meter 1 Readings: `162906459` to `162856672` = `49,787 L`
   - Final Density @ 20°C: `787.0 kg/m3`, Final VCF: `1.000000`
   - Physical Shortage: `42 L` ($0.084\%$)
   - Allowed Loss ($0.12\%$): `60 L`
   - Result: Shortage is strictly less than contractual allowance $\rightarrow$ **Debit: 0 L (0.00 ETB)**.
4. **Large Loss (> 150 L) Scenario**:
   - If origin is `50,000 L` and discharge is `49,800 L` (Loss = `200 L`):
   - Because $200 > 150 \text{ L}$, the allowance drops to `0 L` $\rightarrow$ **Debited: 200 L @ 319.76 ETB = 63,952.00 ETB**.

---

## 5. Standalone Execution & Verification

You can verify the calculations at any time from the terminal using Python:
```bash
python erpnext/petroleum_calculator.py
```
This runs automated regression tests matching all historical records, ASTM 54B/D lookup grids, and the screenshot presets.
