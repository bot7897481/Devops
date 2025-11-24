<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Apprenticeship Indenture Agreement</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 12pt;
            line-height: 1.6;
            color: #000;
            margin: 40px;
        }
        h1 {
            text-align: center;
            font-size: 18pt;
            margin-bottom: 30px;
        }
        h2 {
            font-size: 14pt;
            margin-top: 20px;
            margin-bottom: 10px;
        }
        .section {
            margin-bottom: 20px;
        }
        .signature-block {
            margin-top: 40px;
            page-break-inside: avoid;
        }
        .signature-line {
            border-top: 1px solid #000;
            width: 300px;
            margin-top: 40px;
            display: inline-block;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
        }
        table, th, td {
            border: 1px solid #000;
        }
        th, td {
            padding: 8px;
            text-align: left;
        }
        th {
            background-color: #f0f0f0;
        }
        .header-info {
            margin-bottom: 30px;
        }
        .info-row {
            margin-bottom: 5px;
        }
    </style>
</head>
<body>
    <h1>APPRENTICESHIP INDENTURE AGREEMENT</h1>
    <h2>LOCAL 39 INTERNATIONAL UNION OF OPERATING ENGINEERS</h2>

    <div class="header-info">
        <p><strong>Date of Agreement:</strong> {{ $generated_date }}</p>
    </div>

    <div class="section">
        <h2>PARTIES TO THIS AGREEMENT:</h2>

        <div class="info-row">
            <strong>APPRENTICE:</strong><br>
            Name: {{ $applicant['name'] }}<br>
            Address: {{ $applicant['address'] }}<br>
            Phone: {{ $applicant['phone'] }}<br>
            Email: {{ $applicant['email'] }}
        </div>

        <div class="info-row" style="margin-top: 15px;">
            <strong>EMPLOYER:</strong><br>
            Company Name: {{ $employer['company_name'] }}<br>
            Location: {{ $employer['location'] }}
        </div>

        <div class="info-row" style="margin-top: 15px;">
            <strong>UNION:</strong><br>
            {{ $union['name'] }}<br>
            {{ $union['address'] }}
        </div>
    </div>

    <div class="section">
        <h2>AGREEMENT TERMS:</h2>

        <p><strong>Start Date:</strong> {{ $contract_details['start_date'] }}</p>
        <p><strong>Term Length:</strong> {{ $contract_details['term_length_years'] }} years</p>

        <p><strong>Job Description:</strong></p>
        <p>{{ $contract_details['job_description'] }}</p>
    </div>

    <div class="section">
        <h2>WAGE SCHEDULE:</h2>
        <p>The apprentice shall receive wages as a percentage of the journeyman wage according to the following schedule:</p>

        <table>
            <thead>
                <tr>
                    <th>Year</th>
                    <th>Percentage of Journeyman Wage</th>
                </tr>
            </thead>
            <tbody>
                @foreach($contract_details['wage_schedule'] as $schedule)
                <tr>
                    <td>Year {{ $schedule['year'] }}</td>
                    <td>{{ $schedule['percentage'] }}%</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <div class="section">
        <h2>GENERAL PROVISIONS:</h2>

        <ol>
            <li>The apprentice agrees to diligently and faithfully perform the work of the trade and such other work as the employer may direct.</li>
            <li>The apprentice agrees to attend and satisfactorily complete the required hours of related and supplemental instruction.</li>
            <li>The employer agrees to provide suitable employment and training opportunities for the apprentice to learn the stationary engineer trade.</li>
            <li>The employer agrees to pay the apprentice the wages specified in the wage schedule above.</li>
            <li>This agreement may be terminated by mutual consent of all parties, or in accordance with applicable regulations.</li>
            <li>The apprentice agrees to comply with all safety regulations and workplace policies.</li>
            <li>Upon successful completion of the apprenticeship term and meeting all requirements, the apprentice will be recognized as a journeyman stationary engineer.</li>
        </ol>
    </div>

    <div class="section">
        <h2>TRAINING REQUIREMENTS:</h2>
        <p>The apprentice must complete:</p>
        <ul>
            <li>Minimum of 8,000 hours of on-the-job training over the term of the apprenticeship</li>
            <li>Required classroom instruction hours as mandated by the California Division of Apprenticeship Standards</li>
            <li>All safety certifications required for the trade</li>
            <li>Maintain satisfactory performance evaluations throughout the apprenticeship period</li>
        </ul>
    </div>

    <div class="signature-block">
        <h2>SIGNATURES:</h2>

        <p>By signing below, all parties agree to the terms and conditions of this apprenticeship indenture agreement.</p>

        <div style="margin-top: 60px;">
            <div class="signature-line"></div><br>
            <strong>Apprentice Signature</strong><br>
            Name: {{ $applicant['name'] }}<br>
            Date: _______________________
        </div>

        <div style="margin-top: 60px;">
            <div class="signature-line"></div><br>
            <strong>Employer Representative Signature</strong><br>
            Company: {{ $employer['company_name'] }}<br>
            Date: _______________________
        </div>

        <div style="margin-top: 60px;">
            <div class="signature-line"></div><br>
            <strong>Union Representative Signature</strong><br>
            Organization: {{ $union['name'] }}<br>
            Date: _______________________
        </div>
    </div>

    <div style="margin-top: 60px; font-size: 10pt; text-align: center; color: #666;">
        <p>This document is a legally binding contract. All parties should retain a copy for their records.</p>
        <p>Generated on {{ $generated_date }}</p>
    </div>
</body>
</html>
