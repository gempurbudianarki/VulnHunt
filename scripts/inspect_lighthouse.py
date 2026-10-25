import json

with open('docs/lighthouse-mobile.json', 'r', encoding='utf-8') as f:
    data = json.load(f)

print('--- ACCESSIBILITY AUDITS < 1 ---')
for audit_ref in data['categories']['accessibility']['auditRefs']:
    audit = data['audits'].get(audit_ref['id'])
    if audit and audit.get('score') is not None and audit['score'] < 1:
        print(f"{audit_ref['id']}: score={audit['score']} title={audit.get('title')}")
        for item in audit.get('details', {}).get('items', [])[:3]:
            print('  node:', item.get('node', {}).get('snippet'))

print('\n--- PERFORMANCE AUDITS < 1 ---')
for audit_ref in data['categories']['performance']['auditRefs']:
    audit = data['audits'].get(audit_ref['id'])
    if audit and audit.get('score') is not None and audit['score'] < 1:
        print(f"{audit_ref['id']}: score={audit['score']} title={audit.get('title')} (display: {audit.get('displayValue', '')})")
