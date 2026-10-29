<?php

namespace App\Profiles\Enums;

enum Profession: string
{
    case None = 'none';
    case CpaLawyer = 'cpa_lawyer';
    case DoctorDentist = 'doctor_dentist';
    case EngineerArchitect = 'engineer_architect';
    case Other = 'other';
}
