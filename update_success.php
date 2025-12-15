<!DOCTYPE html>
<html>
<head>
    <title>Booking Updated</title>
    <style>
        body {
            font-family: 'Arial', sans-serif;
            background: linear-gradient(135deg, #000000, #333333);
            color: #FFFFFF;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            margin: 0;
        }

        .card {
            background: #FFFFFF;
            padding: 40px 50px;
            border-radius: 12px;
            text-align: center;
            box-shadow: 0 8px 20px rgba(0,0,0,0.4);
            border: 3px solid #FFD700;
            max-width: 500px;
        }

        h2 {
            color: #000000;
            margin-bottom: 30px;
            font-family: 'Arial Black', sans-serif;
            border-bottom: 2px solid #FFD700;
            padding-bottom: 15px;
        }

        .success-message {
            color: #000000;
            font-size: 18px;
            margin-bottom: 30px;
            line-height: 1.6;
        }

        .success-icon {
            font-size: 48px;
            color: #FFD700;
            margin-bottom: 20px;
        }

        a {
            display: inline-block;
            margin-top: 10px;
            padding: 12px 30px;
            background: #000000;
            color: #FFFFFF;
            text-decoration: none;
            border-radius: 8px;
            font-weight: bold;
            transition: 0.3s;
            border: 2px solid #000000;
        }

        a:hover {
            background: #FFD700;
            color: #000000;
            border-color: #FFD700;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(255, 215, 0, 0.3);
        }

        a:active {
            transform: translateY(0);
        }
    </style>
</head>
<body>

    <div class="card">
        <div class="success-icon">✓</div>
        <h2>Booking Updated Successfully</h2>
        <div class="success-message">
            The booking has been successfully updated with the new details.
        </div>
        <a href="TrainerClients.php">Back to Clients</a>
    </div>

</body>
</html>