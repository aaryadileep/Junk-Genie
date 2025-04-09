// Firebase configuration (optional - use if you need Firebase features)
const firebaseConfig = {
  apiKey: "AIzaSyBVHFcLOVfBXv5PejKbbmO9ICvPf04ukDw",
  authDomain: "login-4a49b.firebaseapp.com",
  projectId: "login-4a49b",
  storageBucket: "login-4a49b.appspot.com",
  messagingSenderId: "941045424561",
  appId: "1:941045424561:web:61142fa677c5eda889dd90"
};

// Initialize Firebase (if using)
firebase.initializeApp(firebaseConfig);

// Alternative manual Google Sign-In (if not using HTML button)
document.getElementById('custom-google-button')?.addEventListener('click', () => {
  const auth = firebase.auth();
  const provider = new firebase.auth.GoogleAuthProvider();
  
  auth.signInWithPopup(provider)
      .then((result) => {
          // Handle successful login
          window.location.href = result.user?.emailVerified 
              ? 'dashboard.php' 
              : 'verify-email.php';
      })
      .catch((error) => {
          console.error("Google Sign-In Error:", error);
          alert("Login failed: " + error.message);
      });
});