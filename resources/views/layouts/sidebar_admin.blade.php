<style>

body{
margin:0;
}


/* =====================
SIDEBAR
===================== */

.sidebar{

width:250px;

height:100vh;

background:#1e272e;

position:fixed;

left:0;

top:0;

padding-top:20px;

transition:.3s;

overflow:hidden;

z-index:999;

}



/*
Sidebar tutup
geser keluar
*/

.sidebar.close{

transform:

translateX(

-250px

);

}





.logo{

color:white;

font-size:25px;

font-weight:bold;

text-align:center;

margin-bottom:30px;

}





.sidebar a{

display:flex;

align-items:center;

gap:12px;

padding:14px 20px;

color:white;

text-decoration:none;

transition:.2s;

}



.sidebar a:hover{

background:#273c75;

}






/* =====================
BURGER
SELALU MUNCUL
===================== */

.toggle-btn{

position:fixed;

top:20px;

left:15px;

z-index:1001;

background:#273c75;

border:none;

color:white;

padding:10px 14px;

border-radius:8px;

cursor:pointer;

font-size:20px;

}




/*
Saat sidebar buka
burger geser
*/

.sidebar:not(.close)

~

.toggle-btn{

left:

265px;

}





/* =====================
CONTENT
===================== */

.content{

margin-left:250px;

padding:25px;

transition:.3s;

}



.content.full{

margin-left:0;

}



</style>





<!-- BURGER -->
<button

class="toggle-btn"

onclick="toggleSidebar()"

>

☰

</button>





<!-- SIDEBAR -->

<div

id="sidebar"

class="sidebar"

>



<div class="logo">

🎓 Admin

</div>





<a href="/dashboard/admin">

📊 Dashboard

</a>




<a href="/dashboard/admin/siswa">

👨‍🎓 Kelola Siswa

</a>




<a href="/dashboard/admin/siswa/import">

📥 Import Siswa

</a>




<a href="/dashboard/admin/guru">

👩‍🏫 Kelola Guru

</a>




<a href="/dashboard/admin/wali-kelas">

🏫 Wali Kelas

</a>




<a href="/dashboard/admin/guru-piket">

📝 Guru Piket

</a>




<a href="/dashboard/admin/kelas">

🏢 Kelola Kelas

</a>




<a href="/dashboard/admin/jurusan">

📚 Jurusan

</a>




<a href="/dashboard/admin/jadwal">

🗓 Jadwal

</a>




<a href="/dashboard/admin/nilai">

📈 Monitoring Nilai

</a>




<a href="/dashboard/admin/absensi/rekap">

📋 Rekap Absensi

</a>




<a href="/logout">

🚪 Logout

</a>



</div>







<script>

function toggleSidebar(){


document

.getElementById(

'sidebar'

)

.classList

.toggle(

'close'

);




document

.getElementById(

'content'

)

.classList

.toggle(

'full'

);


}

</script>