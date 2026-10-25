<!DOCTYPE html>
<html>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>Surat Perjanjian Kerahasiaan (NDA) - {{ $documentNumber }}</title>
    <style>
        body {
            font-family: 'Helvetica', 'Arial', sans-serif;
            font-size: 11pt;
            line-height: 1.5;
            color: #17302A;
            margin: 20px 30px;
        }
        .header {
            text-align: center;
            border-bottom: 2px solid #17302A;
            padding-bottom: 12px;
            margin-bottom: 20px;
        }
        .header h1 {
            font-size: 15pt;
            margin: 0;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        .header h2 {
            font-size: 12pt;
            margin: 4px 0 0 0;
            font-weight: normal;
        }
        .doc-meta {
            text-align: right;
            font-size: 9pt;
            font-family: monospace;
            color: #5E6D68;
            margin-bottom: 15px;
        }
        .table-data {
            width: 100%;
            margin-bottom: 15px;
            font-size: 10.5pt;
        }
        .table-data td {
            padding: 3px 0;
            vertical-align: top;
        }
        .table-data td.label {
            width: 25%;
            font-weight: bold;
        }
        .table-data td.colon {
            width: 3%;
        }
        .clause {
            margin-bottom: 12px;
            text-align: justify;
            font-size: 10pt;
        }
        .clause-title {
            font-weight: bold;
            margin-bottom: 2px;
        }
        .signature-section {
            margin-top: 35px;
            width: 100%;
        }
        .signature-box {
            width: 45%;
            float: right;
            text-align: center;
            font-size: 10pt;
        }
        .signature-line {
            margin-top: 65px;
            border-bottom: 1px solid #17302A;
            font-weight: bold;
        }
        .footer-note {
            clear: both;
            margin-top: 40px;
            font-size: 8pt;
            color: #5E6D68;
            border-top: 1px dashed #5E6D68;
            padding-top: 8px;
            text-align: center;
        }
    </style>
</head>
@php
    $logoPdfPath = public_path('brand/logo-pdf.png');
    $logoBase64 = file_exists($logoPdfPath) ? 'data:image/png;base64,' . base64_encode(file_get_contents($logoPdfPath)) : null;
@endphp
<body>
    <div class="header">
        <table style="width: 100%; border: none; margin-bottom: 0;">
            <tr>
                @if($logoBase64)
                    <td style="width: 65px; vertical-align: middle; text-align: left; padding: 0;">
                        <img src="{{ $logoBase64 }}" style="width: 55px; height: auto;" alt="Logo VulnHunt">
                    </td>
                @endif
                <td style="vertical-align: middle; text-align: center; padding: 0;">
                    <h1 style="font-size: 14pt; margin: 0; text-transform: uppercase; letter-spacing: 0.5px;">Universitas Bina Bangsa Getsempena</h1>
                    <h2 style="font-size: 11pt; margin: 3px 0 0 0; color: #253AA1; font-weight: bold;">Panitia Pelaksana Lomba Bug Bounty Kampus (VulnHunt 2026)</h2>
                </td>
            </tr>
        </table>
    </div>

    <div class="doc-meta">
        Nomor Dokumen: {{ $documentNumber }} | Versi Template: {{ $templateVersion }}
    </div>

    <p style="text-align: center; font-weight: bold; font-size: 12pt; margin-bottom: 15px;">
        SURAT PERJANJIAN KERAHASIAAN DAN KETENTUAN PENGUJIAN KEAMANAN<br/>
        (NON-DISCLOSURE AGREEMENT & SAFE HARBOR)
    </p>

    <p class="clause">
        Pada hari ini, bertanda tangan di bawah ini peserta Lomba Bug Bounty Kampus UBBG 2026:
    </p>

    <table class="table-data">
        <tr>
            <td class="label">Nama Lengkap</td>
            <td class="colon">:</td>
            <td><strong>{{ $user->name }}</strong></td>
        </tr>
        <tr>
            <td class="label">NIM</td>
            <td class="colon">:</td>
            <td>{{ $user->nim ?? '-' }}</td>
        </tr>
        <tr>
            <td class="label">Email Kampus</td>
            <td class="colon">:</td>
            <td>{{ $user->email }}</td>
        </tr>
        <tr>
            <td class="label">Program Studi / Angkatan</td>
            <td class="colon">:</td>
            <td>{{ $user->study_program ?? '-' }} / {{ $user->cohort_year ?? '-' }}</td>
        </tr>
        <tr>
            <td class="label">Nama Tim (Ekspedisi)</td>
            <td class="colon">:</td>
            <td><strong>{{ $team ? $team->name : 'Independen / Menunggu Penetapan Tim' }}</strong></td>
        </tr>
    </table>

    @if(!empty($customText))
        <div class="clause" style="white-space: pre-line;">
            {!! nl2br(e($customText)) !!}
        </div>
    @else
        <div class="clause">
            <div class="clause-title">Pasal 1: Kerahasiaan Informasi Kerentanan</div>
            Saya menyatakan dan berjanji secara sadar bahwa seluruh temuan kerentanan, bukti eksploitasi (Proof-of-Concept), data teknis, maupun informasi lain yang diperoleh selama proses pengujian aset UBBG bersifat <strong>RAHASIA</strong>. Saya tidak akan membocorkan, mempublikasikan, maupun membagikan temuan tersebut kepada pihak mana pun tanpa izin tertulis dari Panitia UBBG.
        </div>

        <div class="clause">
            <div class="clause-title">Pasal 2: Batasan Etis Pengujian</div>
            Saya berjanji hanya melakukan pengujian pada target yang dinyatakan resmi dalam scope perlombaan, tidak melakukan serangan penolakan layanan (DoS/DDoS), tidak merusak atau menghapus data, dan tidak mengakses maupun mengunduh data pribadi sivitas akademika kampus.
        </div>

        <div class="clause">
            <div class="clause-title">Pasal 3: Perlindungan Safe Harbor</div>
            Pihak Universitas Bina Bangsa Getsempena menjamin perlindungan hukum (safe harbor) kepada peserta selama mematuhi seluruh klausul perjanjian ini dan Rules of Engagement yang berlaku.
        </div>
    @endif

    <div class="signature-section">
        <div class="signature-box">
            Banda Aceh, {{ date('d F Y') }}<br/>
            Peserta yang menyatakan,<br/>
            <div class="signature-line">
                {{ $user->name }}
            </div>
            NIM: {{ $user->nim ?? '..................................' }}
        </div>
    </div>

    <div class="footer-note">
        Dokumen resmi terbitan sistem UBBG VulnHunt. Verifikasi keaslian dokumen via hash SHA-256 berkas unggahan bertanda tangan.
    </div>
</body>
</html>
