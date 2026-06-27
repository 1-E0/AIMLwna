cara run SETUP 
cd smart-agri-ai
.\venv\Scripts\Activate.ps1
uvicorn main:app --reload

smart-agri-web
php artisan serve --port=8001
http://127.0.0.1:8001/optimasi
