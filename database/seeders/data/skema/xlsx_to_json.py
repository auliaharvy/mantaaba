"""Ekstrak workbook skema (format Rumah Mantaaba) menjadi JSON template skema.
Pemakaian (butuh: pip install openpyxl): python3 database/seeders/data/skema/xlsx_to_json.py SKEMA.xlsx SIA "Sistem Informasi Akuntansi" "Bulan 4" database/seeders/data/skema/SIA.json
Workbook harus berformat sama dengan skema FND/SIM: sheet "Pemetaaan Standar Kompetensi", satu sheet per unit (MTB-XXX-0001), dan "Audit dan Panduan Penilaian".
"""
import json, re, sys
import openpyxl

path, code, name, month, out = sys.argv[1:6]
wb = openpyxl.load_workbook(path, data_only=True)


def txt(v):
    return re.sub(r'[ \t]+', ' ', str(v)).strip() if v not in (None, '') else ''


def rows(ws):
    for r in ws.iter_rows(values_only=True):
        yield [txt(v) for v in r] + [''] * 6


DIMENSIONS = {'knowledge': 'knowledge', 'skill': 'skill', 'character': 'character', 'social': 'social_cohesion'}

units, unit_sheets = [], [ws for ws in wb.worksheets if re.match(r'^MTB-[A-Z]+-\d{4}$', ws.title)]
skema_title = ''
for ws in unit_sheets:
    unit = {'code': ws.title, 'title': '', 'description': '', 'elements': [], 'assessment': [], 'context': {}}
    section = None
    for r in rows(ws):
        label = r[0].upper()
        if label == 'SKEMA':
            skema_title = skema_title or r[2]
        elif label == 'JUDUL UNIT':
            unit['title'] = r[2]
        elif label == 'DESKRIPSI UNIT':
            unit['description'] = r[2]
        elif label.startswith('ELEMEN UNIT'):
            section = 'elements'
        elif label.startswith('ATURAN PENILAIAN'):
            section = 'assessment'
        elif label.startswith('BUKTI KOMPETENSI'):
            section = 'context'
        elif label.startswith('CHECKLIST') or label.startswith('REKAP') or label.startswith('PENCAPAIAN') or label.startswith('VERIFIKASI'):
            section = None
        elif section == 'elements' and re.match(r'^\d+(\.0)?$', r[0]) and r[1]:
            criteria = [{'no': m.group(1), 'text': m.group(2).strip()} for m in re.finditer(r'(?m)^\s*(\d+\.\d+)\s+(.+?)\s*$', r[3])]
            unit['elements'].append({'no': r[0].split('.')[0], 'title': r[1], 'criteria': criteria})
        elif section == 'assessment' and re.match(r'^\d+(\.0)?$', r[0]) and r[1]:
            key = next(v for k, v in DIMENSIONS.items() if r[1].lower().startswith(k))
            indicator, _, evidence = r[3].partition('Bukti/metode:')
            unit['assessment'].append({'dimension': key, 'label': r[1], 'weight': round(float(r[2]) * 100), 'indicator': indicator.strip(), 'evidence': evidence.strip()})
        elif section == 'context' and r[0] and r[2]:
            unit['context'][r[0]] = r[2]
    if not unit['context']:
        del unit['context']
    units.append(unit)

# Pemetaan standar kompetensi: tujuan utama, fungsi kunci → fungsi utama → fungsi dasar/unit
mapping, objectives, notes = [], '', []
codes = [u['code'] for u in units]
ws = wb['Pemetaaan Standar Kompetensi']
key_fn = None
for r in rows(ws):
    if r[0] and r[0] not in ('Tujuan Utama', 'Lembaga', 'Skema') and not objectives and (r[2] or r[3]) and len(r[0]) > 40:
        objectives = r[0]
    if r[0] in ('Tujuan Utama',) or r[0].startswith('A.') or r[0].startswith('B.'):
        continue
    if r[2] and len(r[2]) > 20:
        key_fn = r[2]
    unit_code = re.match(r'^(MTB-[A-Z]+-\d{4})', r[4])
    if unit_code:  # format SIM: kolom Fungsi Dasar berisi "KODE | judul"
        mapping.append({'key_function': key_fn, 'main_function': r[3], 'units': [unit_code.group(1)]})
    elif r[3] and r[4] and not r[3].startswith('%'):  # format Fundamental: fungsi utama + fungsi dasar
        mapping.append({'key_function': key_fn, 'main_function': r[3], 'basic_functions': [r[4]], 'units': []})
    elif r[4] and mapping and not mapping[-1]['units'] and 'basic_functions' in mapping[-1]:
        mapping[-1]['basic_functions'].append(r[4])
    elif r[0] in ('Target pembelajaran', 'Status rujukan SKKNI', 'Bukti penilaian', 'Alur lanjutan', 'Sumber utama') and r[1]:
        notes.append({'topic': r[0], 'text': r[1]})
if mapping and not any(m['units'] for m in mapping):  # Fundamental: fungsi utama berurutan = unit berurutan
    for m, c in zip(mapping, codes):
        m['units'] = [c]

guide, portfolio = [], []
ws = wb['Audit dan Panduan Penilaian']
in_matrix = False
for r in rows(ws):
    if r[0].startswith('MATRIKS PEMERIKSAAN'):
        in_matrix = True
        continue
    if r[0].startswith('PANDUAN ASESMEN') or r[0].startswith('PAKET PORTOFOLIO'):
        in_matrix = False
    if re.match(r'^\d{2}$', r[0]) and r[1] and r[2]:
        portfolio.append({'no': r[0], 'output': r[1], 'units': r[2]})
    elif not in_matrix and r[0] and r[1] and not r[0].startswith('MTB-') and r[0] not in ('No', 'Kode') and not r[0].upper().startswith('AUDIT'):
        guide.append({'topic': re.sub(r'\s*\|.*$', '', r[0]), 'text': r[1]})

data = {
    '$schema_note': 'Template skema Rumah Mantaaba. Satu file = satu skema; units = unit kompetensi (modul). Impor lewat Dashboard › Skema › Impor JSON atau `php artisan skema:import`.',
    'code': code, 'name': name, 'title': skema_title, 'month_label': month, 'objectives': objectives,
    'competency_map': mapping, 'units': units, 'final_portfolio': portfolio, 'assessment_guide': guide, 'notes': notes,
}
json.dump(data, open(out, 'w'), ensure_ascii=False, indent=2)
print(out, len(units), 'unit', sum(len(e['criteria']) for u in units for e in u['elements']), 'KUK', len(mapping), 'peta', len(guide), 'panduan', len(portfolio), 'portofolio')
