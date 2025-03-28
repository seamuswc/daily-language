<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>今日の日本語</title>
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
        .container { max-width: 600px; margin: 0 auto; padding: 20px; }
        .header { text-align: center; margin-bottom: 20px; }
        .sentence { background: #f9f9f9; padding: 15px; border-radius: 5px; margin-bottom: 20px; }
        .section { margin-bottom: 15px; }
        .label { font-weight: bold; color: #2c3e50; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>今日の日本語 (Today's Japanese)</h1>
        </div>
        
        <div class="sentence">
            <div class="section">
                <span class="label">漢字:</span> {{ $sentence['kanji'] }}
            </div>
            <div class="section">
                <span class="label">ひらがな:</span> {{ $sentence['hiragana'] }}
            </div>
            <div class="section">
                <span class="label">Romaji:</span> {{ $sentence['romaji'] }}
            </div>
        </div>
        
        <div class="section">
            <h2 class="label">単語の解説 (Breakdown)</h2>
            <p>{{ $sentence['breakdown'] }}</p>
        </div>
        
        <div class="section">
            <h2 class="label">文法の説明 (Grammar)</h2>
            <p>{{ $sentence['grammar'] }}</p>
        </div>
        
        <div class="footer" style="margin-top: 30px; text-align: center; font-size: 0.9em; color: #7f8c8d;">
            <p>このメールは自動送信されています。返信しないでください。</p>
            <p>This email is automatically generated. Please do not reply.</p>
        </div>
    </div>
</body>
</html>