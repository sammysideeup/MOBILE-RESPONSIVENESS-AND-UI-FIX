<?php
session_start();
include 'connection.php';

// Check for user login (using user_id which is now set)
if (!isset($_SESSION['user_id'])) {
    // If not logged in as user, check if logged in as trainer
    if (!isset($_SESSION['trainer_id'])) {
        // Neither user nor trainer - redirect to login
        header("Location: Loginpage.php");
        exit();
    } else {
        // Trainer logged in - they shouldn't book sessions for themselves
        // Redirect or show error
        die("Trainers cannot book sessions. Please log in as a member.");
    }
}

$user_id = $_SESSION['user_id'];
$trainer_id = $_GET['trainer_id'] ?? null;

if (!$trainer_id) {
    die("Trainer not found.");
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8" />
<meta name="viewport" content="width=device-width,initial-scale=1" />
<title>Booking & Payment — ECG Gym</title>
<style>
  :root{
    --bg:#808080; --card:#fff; --accent:#5b42f3; --muted:#666; --danger:#e74c3c; --success:#27ae60;
  }
  *{box-sizing:border-box}
  body{font-family:Inter,system-ui,Arial; margin:0; background:var(--bg); color:#222; display:flex; justify-content:center; padding:40px;}
  .card{background:var(--card); border-radius:12px; box-shadow:0 6px 20px rgba(0,0,0,.12); padding:24px; width:480px;}
  h1{margin:0 0 12px; font-size:22px}
  .muted{color:var(--muted)}
  .flex{display:flex; justify-content:space-between; margin:8px 0}
  .btn{background:var(--accent); color:#fff; border:none; padding:12px; border-radius:8px; cursor:pointer; width:100%; margin-top:16px;}
  .calendar-grid{display:grid; grid-template-columns:repeat(7,1fr); gap:6px; margin-top:12px;}
  .dayName{text-align:center; font-size:12px; color:var(--muted); font-weight:600}
  .day{min-height:44px; display:flex; align-items:center; justify-content:center; background:#f6f6f6; border-radius:8px; cursor:pointer; transition:all .12s}
  .day:hover{transform:translateY(-3px)}
  .day.disabled{background:#eee; color:#aaa; cursor:not-allowed; transform:none}
  .day.selected{background:var(--accent); color:#fff; font-weight:700}
  .slots{display:flex; flex-wrap:wrap; gap:8px; margin-top:14px}
  .slot{padding:8px 12px; border-radius:8px; background:#f0f0f0; cursor:pointer}
  .slot.booked{background:#ffd6d6; color:#a00; cursor:not-allowed}
  .slot.selected{background:var(--accent); color:#fff}
  .receipt{display:none; border-top:1px solid #ccc; margin-top:20px; padding-top:16px;}
  .success{color:var(--success); font-weight:700; margin-bottom:8px;}
  .summary{margin-top:16px; padding:12px; border-radius:8px; background:#f8f8ff; border:1px solid #eee}
  .right-align{text-align:right}
  .cal-header{display:flex; justify-content:space-between; align-items:center; margin-top:12px;}
  .cal-controls button{padding:4px 8px; border-radius:6px; border:none; background:#f2f2f2; cursor:pointer;}
</style>
</head>
<body>

<div class="card" id="bookingCard">
  <a href="TrainerBooking.php?trainer_id=<?php echo $trainer_id; ?>" 
   style="display:inline-block; margin-bottom:10px; text-decoration:none; color:#5b42f3; border:2px solid #5b42f3; padding:8px 14px; border-radius:8px;">
   ← Back
  </a>

  <h1>Book Training Session</h1>
  <p class="muted">Pick a date on the calendar and select an available time slot.</p>

  <label for="sessions">Number of Sessions</label>
  <select id="sessions">
    <option value="1">12 session (Boxing) - ₱5000</option>
    <option value="1">12 session (Muay Thai) - ₱5000</option>
    <option value="1">12 session (Circuit Training) - ₱4500</option>
    <option value="1">12 session (Weight Lifting Training) - ₱4500</option>

  </select>

  <div class="cal-header">
    <button id="prevMonth">&lt;</button>
    <div id="monthYear" class="muted"></div>
    <button id="nextMonth">&gt;</button>
  </div>

  <div id="calendarContainer">
    <div class="calendar-grid" id="calendar"></div>
  </div>

  <div style="margin-top:12px">
    <div class="muted">Available Time Slots</div>
    <div class="slots" id="slotsContainer"></div>
  </div>

  <div class="summary">
    <div class="flex">
      <div><div class="muted">Selected Date</div><div id="selectedDate">—</div></div>
      <div class="right-align"><div class="muted">Selected Time</div><div id="selectedTime">—</div></div>
    </div>
    <div class="muted">Price: <strong id="price">₱1</strong></div>
  </div>

  <button id="proceedBtn" class="btn">Proceed to Payment →</button>

</div>
    
<div class="card" id="paymentCard" style="display:none">
  <h1>Payment</h1>
  <div class="flex"><span>Date:</span><span id="payDate">—</span></div>
  <div class="flex"><span>Time:</span><span id="payTime">—</span></div>
  <div class="flex"><span>Sessions:</span><span id="paySessions">—</span></div>
  <div class="flex"><span>Total:</span><span id="payPrice">₱0</span></div>

  <!-- single PAYPAL container -->
  <div id="paypal-container-NVCYWM4K27QHW" style="margin-top:15px;"></div>

  <!-- CHECKOUT WITHOUT PAYMENT BUTTON -->
  <button id="checkoutBtn" class="btn" style="background:#5b42f3; margin-top:8px;">
    Checkout →
  </button>

  <div class="receipt" id="receipt">
    <div class="success">Payment Successful!</div>
    <div class="flex"><span>Booking ID:</span><span id="receiptId">—</span></div>
    <div class="flex"><span>Date:</span><span id="receiptDate">—</span></div>
    <div class="flex"><span>Time:</span><span id="receiptTime">—</span></div>
    <div class="flex"><span>Sessions:</span><span id="receiptSessions">—</span></div>
    <div class="flex"><span>Total Paid:</span><span id="receiptPrice">₱0</span></div>
  </div>
</div>

<!-- PAYPAL SDK (hosted-buttons used once) -->
<script 
  src="https://www.paypal.com/sdk/js?client-id=BAAw8_HGuKNx5bQqABjtgfVSFBYYCnvdjXDbvkjbgjfGsnT_ktu98RhnLwPo5tcJ7jrN34AC2uB7srIl2U&components=hosted-buttons&currency=PHP">
</script>

<script>
/* ---------- Config & State ---------- */
const baseSlots = ['08:00','10:00','13:00','15:00','17:00','19:00'];
const slotCapacity = 1; // per slot per trainer
const pricePerSession = 1; // update if you want real pricing
let now = new Date();
let viewYear = now.getFullYear();
let viewMonth = now.getMonth();
let selected = {date:null, slot:null};
const calendarEl = document.getElementById('calendar');
const trainerId = "<?php echo intval($trainer_id); ?>"; // used in fetches

// bookings cache for this trainer
let bookingsCache = null;

/* ---------- Helpers ---------- */
function formatDateYMD(y,m,d){ 
    m=String(m+1).padStart(2,'0'); 
    d=String(d).padStart(2,'0'); 
    return `${y}-${m}-${d}`; 
}

/* ---------- Load bookings once (for current trainer) ---------- */
async function loadBookings(refresh = false) {
    if (bookingsCache && !refresh) return bookingsCache;
    // attach trainer_id param so server returns only that trainer's bookings
    const res = await fetch(`fetch_booking.php?trainer_id=${trainerId}`);
    const js = await res.json();
    bookingsCache = Array.isArray(js) ? js : [];
    return bookingsCache;
}

/* ---------- Build calendar (async) ---------- */
async function buildCalendar(year, month) {
    const bookings = await loadBookings();
    calendarEl.innerHTML = '';

    const dayNames = ["Sun","Mon","Tue","Wed","Thu","Fri","Sat"];
    dayNames.forEach(dn => {
        const el = document.createElement('div');
        el.className = 'dayName';
        el.textContent = dn;
        calendarEl.appendChild(el);
    });

    document.getElementById('monthYear').textContent =
        new Intl.DateTimeFormat('en', {month:'long'}).format(new Date(year,month)) + ' ' + year;

    const firstDay = new Date(year, month, 1).getDay();
    const daysInMonth = new Date(year, month + 1, 0).getDate();
    const today = new Date();

    // Fill empty spaces
    for (let i = 0; i < firstDay; i++) {
        calendarEl.appendChild(document.createElement('div'));
    }

    for (let d = 1; d <= daysInMonth; d++) {
        const dayEl = document.createElement('div');
        dayEl.className = 'day';
        dayEl.textContent = d;

        const ymd = formatDateYMD(year, month, d);
        const thisDate = new Date(year, month, d);

        // check if all slots are booked for this trainer/date
        const allSlotsBooked = baseSlots.every(s => {
            return bookings.some(b => b.date === ymd && b.time === s);
        });

        const isPast = thisDate < new Date(today.getFullYear(), today.getMonth(), today.getDate());

        if (isPast || allSlotsBooked) {
            dayEl.classList.add('disabled');
        } else {
            dayEl.addEventListener('click', async () => {
                // if clicking a new date, refresh the slots view
                selected.date = ymd;
                selected.slot = null;
                // ensure we render slots AFTER selected updated
                await renderSlots();
                updateSummary();
                document.querySelectorAll('.day').forEach(n => n.classList.remove('selected'));
                dayEl.classList.add('selected');
            });
        }

        // Highlight today
        if (year === today.getFullYear() &&
            month === today.getMonth() &&
            d === today.getDate()) {
                dayEl.style.outline = '2px solid rgba(90,63,243,.12)';
        }

        calendarEl.appendChild(dayEl);
    }
}

/* ---------- Render slots (async) ---------- */
async function renderSlots() {
    const container = document.getElementById('slotsContainer');
    container.innerHTML = '';

    if (!selected.date) {
        container.innerHTML = '<div class="muted">Select a date to view slots.</div>';
        return;
    }

    const bookings = await loadBookings();

    baseSlots.forEach(s => {
        const btn = document.createElement('div');
        btn.className = 'slot';
        btn.textContent = s;

        const isFull = bookings.some(b => b.date === selected.date && b.time === s);

        if (isFull) {
            btn.classList.add('booked');
            btn.style.pointerEvents = "none";
            btn.style.opacity = "0.5";
        } else {
            btn.addEventListener('click', () => {
                document.querySelectorAll('.slot').forEach(x => x.classList.remove('selected'));
                btn.classList.add('selected');
                selected.slot = s;
                updateSummary();
            });
        }

        container.appendChild(btn);
    });
}

/* ---------- UI Summary ---------- */
const selectedDateEl=document.getElementById('selectedDate'), 
      selectedTimeEl=document.getElementById('selectedTime'), 
      priceEl=document.getElementById('price'), 
      sessionsSel=document.getElementById('sessions');

function updateSummary(){ 
    selectedDateEl.textContent = selected.date || '—'; 
    selectedTimeEl.textContent = selected.slot || '—'; 
    const sessions = Number(sessionsSel.value); 
    const amount = sessions * pricePerSession; 
    priceEl.textContent = `₱${amount}`; 
}

/* ---------- Month navigation (await builds) ---------- */
document.getElementById('prevMonth').onclick = async () => { 
    viewMonth--; 
    if(viewMonth<0){ viewMonth=11; viewYear--; } 
    await buildCalendar(viewYear,viewMonth);
    await renderSlots();
};
document.getElementById('nextMonth').onclick = async () => { 
    viewMonth++; 
    if(viewMonth>11){ viewMonth=0; viewYear++; } 
    await buildCalendar(viewYear,viewMonth);
    await renderSlots();
};

/* ---------- Initial load ---------- */
(async () => {
    await buildCalendar(viewYear,viewMonth);
    await renderSlots();
})();

/* ---------- Proceed button (shows payment card) ---------- */
document.getElementById('proceedBtn').onclick = () => {
    if(!selected.date || !selected.slot){ 
        alert('Please select a date and time slot.'); 
        return; 
    }
    const sessions = Number(sessionsSel.value);
    const amount = sessions * pricePerSession;
    const booking = {id:'bk_'+Date.now(), date:selected.date, time:selected.slot, sessions, amount};
    localStorage.setItem('selectedBooking', JSON.stringify(booking));
    document.getElementById('bookingCard').style.display='none';
    document.getElementById('paymentCard').style.display='block';
    document.getElementById('payDate').textContent=booking.date;
    document.getElementById('payTime').textContent=booking.time;
    document.getElementById('paySessions').textContent=booking.sessions;
    document.getElementById('payPrice').textContent=`₱${booking.amount}`;
};

/* ---------- Checkout without payment ---------- */
document.getElementById('checkoutBtn').onclick = async () => {
    if(!selected.date || !selected.slot){
        alert('Please select a date and time slot.');
        return;
    }

    const sessions = Number(sessionsSel.value);
    const amount = sessions * pricePerSession;

    const bookingData = new FormData();
    bookingData.append("trainer_id", trainerId);
    bookingData.append("date", selected.date);
    bookingData.append("time", selected.slot);
    bookingData.append("sessions", sessions);
    bookingData.append("total_price", amount);

    const res = await fetch("save_booking.php", { method:"POST", body:bookingData });
    const json = await res.json();

    if (json.status === "success") {
        alert("Booking successful! Booking ID: " + json.booking_id);
        // refresh local bookings and UI
        await loadBookings(true);
        await buildCalendar(viewYear, viewMonth);
        await renderSlots();
      window.location.href = "receipt_trainerbook.php?booking=success&booking_id=" + json.booking_id;
    } else {
        alert("Booking failed: " + json.msg);
        // if slot already taken, refresh bookings and UI to show new state
        await loadBookings(true);
        await buildCalendar(viewYear, viewMonth);
        await renderSlots();
    }
};

/* ---------- PayPal hosted button (single call) ---------- */
paypal.HostedButtons({
    hostedButtonId: "NVCYWM4K27QHW",
    onApprove: async function(data) {

        if (!selected.date || !selected.slot) {
            alert('Please select a date and time slot before payment.');
            return;
        }

        const sessions = Number(sessionsSel.value);
        const amount = sessions * pricePerSession;

        const bookingData = new FormData();
        bookingData.append("trainer_id", trainerId);
        bookingData.append("date", selected.date);
        bookingData.append("time", selected.slot);
        bookingData.append("sessions", sessions);
        bookingData.append("total_price", amount);

        const res = await fetch("save_booking.php", { method: "POST", body: bookingData });
        const json = await res.json();

        if (json.status === "success") {

            // ONLY ONE REDIRECT — straight to receipt
            window.location.href = "receipt_trainerbook.php?booking_id=" + json.booking_id;

        } else {
            alert("Booking failed: " + json.msg);
            await loadBookings(true);
            await buildCalendar(viewYear, viewMonth);
            await renderSlots();
        }
    }
}).render("#paypal-container-NVCYWM4K27QHW");

</script>

</body>
</html>