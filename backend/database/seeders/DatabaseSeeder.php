<?php

namespace Database\Seeders;

use App\Models\Client;
use App\Models\Notification;
use App\Models\ProgressNote;
use App\Models\Project;
use App\Models\Task;
use App\Models\TaskComment;
use App\Models\TimeLog;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Seed Users (2 Admins, 5 Members)
        $admin1 = User::updateOrCreate(['email' => 'admin@projectpulse.test'], [
            'name' => 'Admin ProjectPulse',
            'email' => 'admin@projectpulse.test',
            'password' => Hash::make('password'),
            'role' => 'admin',
            'job_title' => 'Chief Technology Officer',
            'avatar_url' => 'https://images.unsplash.com/photo-1534528741775-53994a69daeb?w=150',
            'is_active' => true,
        ]);

        $admin2 = User::updateOrCreate(['email' => 'pm@projectpulse.test'], [
            'name' => 'Sarah PM',
            'email' => 'pm@projectpulse.test',
            'password' => Hash::make('password'),
            'role' => 'admin',
            'job_title' => 'Lead Project Manager',
            'avatar_url' => 'https://images.unsplash.com/photo-1494790108377-be9c29b29330?w=150',
            'is_active' => true,
        ]);

        $member1 = User::updateOrCreate(['email' => 'member@projectpulse.test'], [
            'name' => 'Member ProjectPulse',
            'email' => 'member@projectpulse.test',
            'password' => Hash::make('password'),
            'role' => 'member',
            'job_title' => 'Full Stack Engineer',
            'avatar_url' => 'https://images.unsplash.com/photo-1507003211169-0a1dd7228f2d?w=150',
            'is_active' => true,
        ]);

        $member2 = User::updateOrCreate(['email' => 'john.dev@projectpulse.test'], [
            'name' => 'John Developer',
            'email' => 'john.dev@projectpulse.test',
            'password' => Hash::make('password'),
            'role' => 'member',
            'job_title' => 'Senior Backend Developer',
            'avatar_url' => 'https://images.unsplash.com/photo-1500648767791-00dcc994a43e?w=150',
            'is_active' => true,
        ]);

        $member3 = User::updateOrCreate(['email' => 'sarah.ui@projectpulse.test'], [
            'name' => 'Sarah Designer',
            'email' => 'sarah.ui@projectpulse.test',
            'password' => Hash::make('password'),
            'role' => 'member',
            'job_title' => 'UI/UX Designer',
            'avatar_url' => 'https://images.unsplash.com/photo-1438761681033-6461ffad8d80?w=150',
            'is_active' => true,
        ]);

        $member4 = User::updateOrCreate(['email' => 'alex.qa@projectpulse.test'], [
            'name' => 'Alex Tester',
            'email' => 'alex.qa@projectpulse.test',
            'password' => Hash::make('password'),
            'role' => 'member',
            'job_title' => 'QA Automation Engineer',
            'avatar_url' => 'https://images.unsplash.com/photo-1472099645785-5658abf4ff4e?w=150',
            'is_active' => true,
        ]);

        $member5 = User::updateOrCreate(['email' => 'mike.ops@projectpulse.test'], [
            'name' => 'Mike DevOps',
            'email' => 'mike.ops@projectpulse.test',
            'password' => Hash::make('password'),
            'role' => 'member',
            'job_title' => 'DevOps Engineer',
            'avatar_url' => 'https://images.unsplash.com/photo-1519085360753-af0119f7cbe7?w=150',
            'is_active' => true,
        ]);

        // 2. Seed 5 Clients
        $client1 = Client::create([
            'name' => 'Budi Santoso',
            'company' => 'Nusantara Fashion Hub',
            'email' => 'budi@nusantarafashion.co.id',
            'phone' => '+6281234567890',
            'address' => 'Jl. Sudirman No. 45, Jakarta Selatan',
            'notes' => 'E-commerce fashion retail client.',
            'created_by' => $admin1->id,
        ]);

        $client2 = Client::create([
            'name' => 'Dewi Lestari',
            'company' => 'Apotek HealthPlus Group',
            'email' => 'dewi@healthplus.id',
            'phone' => '+6281198765432',
            'address' => 'Jl. HR Rasuna Said Blok X5, Jakarta',
            'notes' => 'Healthcare pharmacy chain client.',
            'created_by' => $admin1->id,
        ]);

        $client3 = Client::create([
            'name' => 'Rian Hidayat',
            'company' => 'Logistik Cepat Mandiri',
            'email' => 'rian@logistikcepat.com',
            'phone' => '+6282145678901',
            'address' => 'Kawasan Industri Jababeka 2, Cikarang',
            'notes' => 'Supply chain logistics provider.',
            'created_by' => $admin2->id,
        ]);

        $client4 = Client::create([
            'name' => 'Maya Putri',
            'company' => 'EduTech Indonesia Foundation',
            'email' => 'maya@edutech.or.id',
            'phone' => '+6281567890123',
            'address' => 'Jl. Dago No. 120, Bandung',
            'notes' => 'Non-profit digital learning initiative.',
            'created_by' => $admin2->id,
        ]);

        $client5 = Client::create([
            'name' => 'Hendra Wijaya',
            'company' => 'Kopi Kenangan Nusantara',
            'email' => 'hendra@kopinusantara.id',
            'phone' => '+6281789012345',
            'address' => 'Jl. Gajah Mada No. 88, Surabaya',
            'notes' => 'F&B POS and loyalty system client.',
            'created_by' => $admin1->id,
        ]);

        // 3. Seed 5 Projects
        $today = Carbon::today();
        $yesterday = Carbon::yesterday()->toDateString();
        $tomorrow = Carbon::tomorrow()->toDateString();
        $nextWeek = Carbon::today()->addDays(7)->toDateString();
        $lastWeek = Carbon::today()->subDays(7)->toDateString();

        $project1 = Project::create([
            'client_id' => $client1->id,
            'name' => 'Nusantara E-Commerce Portal',
            'description' => 'Multi-tenant online marketplace for local fashion brands with payment gateway integration.',
            'client_brief' => 'Build a responsive web application and mobile app with product catalog, cart, checkout, payment gateway, and admin order tracking.',
            'start_date' => $lastWeek,
            'deadline' => $nextWeek,
            'status' => 'active',
            'created_by' => $admin1->id,
        ]);

        $project2 = Project::create([
            'client_id' => $client2->id,
            'name' => 'HealthPlus Telemedicine App',
            'description' => 'Online doctor consultation and prescription fulfillment mobile app.',
            'client_brief' => 'Real-time video chat, prescription upload, digital invoice, and location-based pharmacy dispatch.',
            'start_date' => Carbon::today()->subDays(20)->toDateString(),
            'deadline' => $tomorrow,
            'status' => 'active',
            'created_by' => $admin1->id,
        ]);

        $project3 = Project::create([
            'client_id' => $client3->id,
            'name' => 'Fleet & Driver Tracking System',
            'description' => 'Real-time GPS tracking dashboard and delivery status updates.',
            'client_brief' => 'Live map overlay, driver route optimization, proof of delivery upload, and SMS alerts.',
            'start_date' => Carbon::today()->subDays(40)->toDateString(),
            'deadline' => $yesterday,
            'status' => 'active',
            'created_by' => $admin2->id,
        ]);

        $project4 = Project::create([
            'client_id' => $client4->id,
            'name' => 'EduTech Learning Platform',
            'description' => 'Interactive student portal with video lectures, quizzes, and certificates.',
            'client_brief' => 'LMS system with video streaming, PDF downloads, auto-graded quizzes, and progress analytics.',
            'start_date' => Carbon::today()->subDays(60)->toDateString(),
            'deadline' => Carbon::today()->subDays(10)->toDateString(),
            'status' => 'completed',
            'created_by' => $admin2->id,
        ]);

        $project5 = Project::create([
            'client_id' => $client5->id,
            'name' => 'Kopi Loyalty & Mobile Ordering',
            'description' => 'QR code ordering and rewards app for coffee shop branches.',
            'client_brief' => 'Scan-to-order tableside, point accumulation, tier discounts, and push notifications.',
            'start_date' => Carbon::today()->addDays(5)->toDateString(),
            'deadline' => Carbon::today()->addDays(30)->toDateString(),
            'status' => 'draft',
            'created_by' => $admin1->id,
        ]);

        // 4. Seed Tasks across Projects
        $tasksData = [
            // Project 1 Tasks
            [
                'project_id' => $project1->id,
                'title' => 'Design Figma Wireframes & Design Tokens',
                'description' => 'Create high-fidelity wireframes for product catalog, checkout, and member profile.',
                'category' => 'design',
                'assignee_id' => $member3->id,
                'priority' => 'high',
                'status' => 'done',
                'estimated_hours' => 12,
                'deadline' => $lastWeek,
                'completed_at' => $lastWeek,
                'source' => 'manual',
            ],
            [
                'project_id' => $project1->id,
                'title' => 'Implement Sanctum Auth & User Profile API',
                'description' => 'Build login, register, logout, and token authorization middleware.',
                'category' => 'backend',
                'assignee_id' => $member1->id,
                'priority' => 'urgent',
                'status' => 'in_progress',
                'estimated_hours' => 8,
                'deadline' => $tomorrow,
                'source' => 'ai',
            ],
            [
                'project_id' => $project1->id,
                'title' => 'Build Product Catalog & Filter UI Component',
                'description' => 'Develop dynamic category filter, search bar, and price range slider in Next.js.',
                'category' => 'frontend',
                'assignee_id' => $member1->id,
                'priority' => 'medium',
                'status' => 'todo',
                'estimated_hours' => 10,
                'deadline' => $nextWeek,
                'source' => 'ai',
            ],
            [
                'project_id' => $project1->id,
                'title' => 'Integrate Midtrans Payment Gateway API',
                'description' => 'Implement Snap checkout popup, webhook notification handler, and transaction status logging.',
                'category' => 'backend',
                'assignee_id' => $member2->id,
                'priority' => 'urgent',
                'status' => 'review',
                'estimated_hours' => 14,
                'deadline' => $today->toDateString(),
                'source' => 'ai',
            ],
            [
                'project_id' => $project1->id,
                'title' => 'Containerize Backend & Web with Docker Compose',
                'description' => 'Write Dockerfiles and docker-compose.yml for local development environment.',
                'category' => 'devops',
                'assignee_id' => $member5->id,
                'priority' => 'high',
                'status' => 'in_progress',
                'estimated_hours' => 6,
                'deadline' => $nextWeek,
                'source' => 'manual',
            ],

            // Project 2 Tasks (Overdue & Due Today)
            [
                'project_id' => $project2->id,
                'title' => 'Fix Telemedicine Video Call Latency Issue',
                'description' => 'Optimize WebRTC peer connection configuration and STUN/TURN server fallback.',
                'category' => 'backend',
                'assignee_id' => $member2->id,
                'priority' => 'urgent',
                'status' => 'in_progress',
                'estimated_hours' => 16,
                'deadline' => $yesterday, // Overdue
                'source' => 'manual',
            ],
            [
                'project_id' => $project2->id,
                'title' => 'Build Pharmacy Dispatch Mobile Screen',
                'description' => 'Ionic React view showing nearby pharmacies, delivery fee calculation, and order tracking.',
                'category' => 'frontend',
                'assignee_id' => $member1->id,
                'priority' => 'high',
                'status' => 'review',
                'estimated_hours' => 8,
                'deadline' => $tomorrow, // H-1
                'source' => 'ai',
            ],
            [
                'project_id' => $project2->id,
                'title' => 'Automated E2E Test Suite for Prescription Upload',
                'description' => 'Write Playwright test scripts covering file upload, validation, and notification trigger.',
                'category' => 'qa',
                'assignee_id' => $member4->id,
                'priority' => 'medium',
                'status' => 'todo',
                'estimated_hours' => 6,
                'deadline' => $nextWeek,
                'source' => 'ai',
            ],

            // Project 3 Tasks
            [
                'project_id' => $project3->id,
                'title' => 'Real-Time Driver GPS Tracking WebSocket Endpoint',
                'description' => 'Implement WebSocket broadcaster for vehicle coordinate streams.',
                'category' => 'backend',
                'assignee_id' => $member2->id,
                'priority' => 'urgent',
                'status' => 'in_progress',
                'estimated_hours' => 12,
                'deadline' => $yesterday, // Overdue
                'source' => 'manual',
            ],
            [
                'project_id' => $project3->id,
                'title' => 'Delivery Proof Photo Upload Modal',
                'description' => 'Add camera capture, file compression, and S3 storage upload component.',
                'category' => 'frontend',
                'assignee_id' => $member1->id,
                'priority' => 'medium',
                'status' => 'done',
                'estimated_hours' => 6,
                'deadline' => $lastWeek,
                'completed_at' => $lastWeek,
                'source' => 'manual',
            ],
        ];

        foreach ($tasksData as $data) {
            $task = Task::create([
                ...$data,
                'created_by' => $admin1->id,
            ]);

            // Seed Time Logs
            if (in_array($task->status, ['in_progress', 'review', 'done'])) {
                TimeLog::create([
                    'task_id' => $task->id,
                    'user_id' => $task->assignee_id ?? $member1->id,
                    'work_date' => Carbon::today()->subDays(2)->toDateString(),
                    'duration_minutes' => rand(120, 360),
                    'note' => 'Worked on core implementation and initial unit test cases.',
                ]);
            }

            // Seed Progress Notes
            if (in_array($task->status, ['in_progress', 'review'])) {
                ProgressNote::create([
                    'task_id' => $task->id,
                    'user_id' => $task->assignee_id ?? $member1->id,
                    'note' => 'Completed initial setup. Currently testing edge cases and validation rules.',
                    'status_snapshot' => $task->status,
                ]);
            }

            // Seed Comments
            TaskComment::create([
                'task_id' => $task->id,
                'user_id' => $admin1->id,
                'body' => "Please review the acceptance criteria for {$task->title} before submitting to review state.",
            ]);

            // Seed Notifications
            if ($task->assignee_id) {
                Notification::create([
                    'id' => (string) Str::uuid(),
                    'user_id' => $task->assignee_id,
                    'type' => 'TaskAssigned',
                    'title' => 'New Task Assigned',
                    'message' => "You have been assigned to task: '{$task->title}'",
                    'data' => ['task_id' => $task->id, 'project_id' => $task->project_id],
                ]);
            }
        }
    }
}
