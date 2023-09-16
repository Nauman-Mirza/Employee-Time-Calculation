# Employee-Time-Calculation
Employee Office and Remote time Calculation Poject in Laravel by Custom Command Scheduler

In this first i collect data in json format through 3rd party mock api the apply some calculation on the data by using following function
1. Go function (in this i collect the data through api by usinh http request)
2. Calculate Function (in this function the time difference between the check_in and check_out time is calculated in minutes)
3. cheeckingOfficeIP function (this function checked the office and remote ip and categories both)
4. mergingTimeForIP function (this function merge the time of same ip's for all the user_id seperately)
5. mergingTimeForIPType function (this function merge all the office and remote ip's for all the user_id seperately)
6. combining function (this function store the remote time and office time for all the users in one array)
7. updateAttendence function (this function identity the attendence status of every employee according to the office time)
7. store function (this function store all the values like userid, remotetime, officetime and attendencestatus)

then i create the custom command through php artsian make: command command_name
then call the first function of our controller in the handle function of custom cammand
then set/schedule the time of custom command daily at 8:00 PM

through this command it will fetech the data from thrid party api and then calculate the time of office and remote IP's and identity the employee attendence status everyday and save this in the database.