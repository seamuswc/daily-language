protected function schedule(Schedule $schedule)
{
    $schedule->command('send:daily-japanese')
        ->dailyAt('09:00') // 9 AM daily
        ->timezone('Asia/Tokyo'); // Adjust to your audience’s timezone
}

protected function commands()
{
    $this->load(__DIR__.'/Commands'); // This line loads commands automatically
    require base_path('routes/console.php');
}