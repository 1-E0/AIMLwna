setup nya pakai 2 terminal 

Terminal 1 cd smart-agri-ai

1    .\venv\Scripts\Activate.ps1
2     pip install fastapi uvicorn scikit-learn numpy pandas pyswarms (kalau blm download kalau udh skip)
3     uvicorn main:app --reload

Terminal 2 cd smart-agri-web

1      php artisan serve --port=8001
2      http://127.0.0.1:8001/optimasi
